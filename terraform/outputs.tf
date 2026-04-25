# ═══════════════════════════════════════════════════════════════
# outputs.tf — Valeurs exportées après terraform apply
# Ces valeurs sont affichées dans le terminal et peuvent être
# utilisées par d'autres modules Terraform
# ═══════════════════════════════════════════════════════════════

output "infrastructure_summary" {
  description = "Résumé de l'infrastructure déployée"
  value = {
    environment  = var.environment
    app_url      = "http://localhost:${var.app_port}"
    mysql_name   = docker_container.mysql.name
    nginx_name   = docker_container.nginx.name
    network      = docker_network.appcs_network.name
  }
}
