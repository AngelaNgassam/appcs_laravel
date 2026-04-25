# ═══════════════════════════════════════════════════════════════
# Terraform — Infrastructure APPCS Laravel
# Automatise la création des ressources Docker avec du code
#
# Terraform = Infrastructure as Code (IaC)
# Au lieu de taper des commandes docker manuellement,
# on décrit l'infrastructure dans un fichier .tf
# et Terraform la crée/modifie/supprime automatiquement
#
# Commandes principales :
#   terraform init    → télécharge les plugins nécessaires
#   terraform plan    → montre ce qui va être créé/modifié
#   terraform apply   → applique les changements
#   terraform destroy → supprime tout
# ═══════════════════════════════════════════════════════════════

terraform {
  required_version = ">= 1.0"
  required_providers {
    docker = {
      source  = "kreuzwerker/docker"
      version = "~> 3.0"
    }
  }
}

# ── Provider Docker ──────────────────────────────────────────
# Indique à Terraform comment communiquer avec Docker
provider "docker" {
  # Sur Windows avec Docker Desktop
  host = "npipe:////./pipe/docker_engine"
}

# ── Variables — valeurs configurables ───────────────────────
variable "app_name" {
  description = "Nom de l'application"
  type        = string
  default     = "appcs"
}

variable "mysql_root_password" {
  description = "Mot de passe root MySQL"
  type        = string
  default     = "root"
  sensitive   = true  # Ne s'affiche pas dans les logs
}

variable "mysql_database" {
  description = "Nom de la base de données"
  type        = string
  default     = "cartes_scolaires"
}

variable "app_port" {
  description = "Port exposé de l'application"
  type        = number
  default     = 8090  # Port différent pour ne pas conflicuter avec docker-compose
}

# ── Réseau Docker ────────────────────────────────────────────
# Tous les conteneurs communiquent sur ce réseau interne
resource "docker_network" "appcs_network" {
  name   = "${var.app_name}_terraform_net"
  driver = "bridge"
}

# ── Volume MySQL ─────────────────────────────────────────────
# Stockage persistant pour MySQL — survit aux redémarrages
resource "docker_volume" "mysql_data" {
  name = "${var.app_name}_mysql_terraform_data"
}

# ── Image MySQL ──────────────────────────────────────────────
resource "docker_image" "mysql" {
  name         = "mysql:8.0"
  keep_locally = true  # Ne supprime pas l'image lors de terraform destroy
}

# ── Conteneur MySQL ──────────────────────────────────────────
resource "docker_container" "mysql" {
  name  = "${var.app_name}_mysql_terraform"
  image = docker_image.mysql.image_id

  # Variables d'environnement MySQL
  env = [
    "MYSQL_ROOT_PASSWORD=${var.mysql_root_password}",
    "MYSQL_DATABASE=${var.mysql_database}",
  ]

  # Monte le volume persistant
  volumes {
    volume_name    = docker_volume.mysql_data.name
    container_path = "/var/lib/mysql"
  }

  # Exposé uniquement en interne (pas de port publique)
  networks_advanced {
    name = docker_network.appcs_network.name
  }

  # Redémarre automatiquement si le conteneur plante
  restart = "unless-stopped"

  # Healthcheck — vérifie que MySQL répond
  healthcheck {
    test         = ["CMD", "mysqladmin", "ping", "-h", "localhost"]
    interval     = "10s"
    timeout      = "5s"
    retries      = 5
    start_period = "30s"
  }
}

# ── Image Laravel (depuis Docker Hub) ───────────────────────
resource "docker_image" "laravel" {
  name         = "angelangassam/appcs-laravel:latest"
  keep_locally = true
}

# ── Image Nginx ──────────────────────────────────────────────
resource "docker_image" "nginx" {
  name         = "nginx:alpine"
  keep_locally = true
}

# ── Conteneur Nginx ──────────────────────────────────────────
resource "docker_container" "nginx" {
  name  = "${var.app_name}_nginx_terraform"
  image = docker_image.nginx.image_id

  # Expose le port sur la machine hôte
  ports {
    internal = 80
    external = var.app_port
  }

  networks_advanced {
    name = docker_network.appcs_network.name
  }

  restart = "unless-stopped"

  # Dépend de Laravel (démarre après)
  depends_on = [docker_container.mysql]
}

# ── Outputs — informations affichées après terraform apply ──
output "mysql_container_name" {
  description = "Nom du conteneur MySQL créé par Terraform"
  value       = docker_container.mysql.name
}

output "nginx_container_name" {
  description = "Nom du conteneur Nginx créé par Terraform"
  value       = docker_container.nginx.name
}

output "app_url" {
  description = "URL d'accès à l'application"
  value       = "http://localhost:${var.app_port}"
}

output "network_name" {
  description = "Réseau Docker créé par Terraform"
  value       = docker_network.appcs_network.name
}
