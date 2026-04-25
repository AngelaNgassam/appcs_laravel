# ═══════════════════════════════════════════════════════════════
# variables.tf — Déclaration des variables Terraform
# Ces valeurs peuvent être surchargées sans modifier main.tf
# ═══════════════════════════════════════════════════════════════

variable "environment" {
  description = "Environnement de déploiement"
  type        = string
  default     = "development"

  # Valeurs autorisées
  validation {
    condition     = contains(["development", "staging", "production"], var.environment)
    error_message = "L'environnement doit être development, staging ou production."
  }
}

variable "mysql_port" {
  description = "Port MySQL exposé sur l'hôte"
  type        = number
  default     = 3308  # Différent de 3306/3307 pour éviter les conflits
}
