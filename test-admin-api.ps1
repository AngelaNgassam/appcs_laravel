# Script de test de l'API Admin
# Usage: .\test-admin-api.ps1

# 1. Login
$loginBody = @{
    email = "admin@test.cm"
    password = "Admin123!"
} | ConvertTo-Json

Write-Host "=== LOGIN ===" -ForegroundColor Cyan
try {
    $loginResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/v1/login" -Method Post -Body $loginBody -ContentType "application/json"

    if ($loginResponse.success) {
        Write-Host "✅ Login réussi" -ForegroundColor Green
        $token = $loginResponse.data.token
        Write-Host "Token: $token`n" -ForegroundColor Yellow
        
        # 2. Test dashboard admin
        Write-Host "=== DASHBOARD ADMIN ===" -ForegroundColor Cyan
        $headers = @{
            "Authorization" = "Bearer $token"
            "Accept" = "application/json"
        }
        
        try {
            $dashboardResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/v1/dashboard/admin" -Headers $headers -Method Get
            
            if ($dashboardResponse.success) {
                Write-Host "✅ Dashboard chargé avec succès`n" -ForegroundColor Green
                Write-Host "Statistiques:" -ForegroundColor Yellow
                Write-Host "- Établissements: $($dashboardResponse.data.statistiques.etablissements.total)" -ForegroundColor White
                Write-Host "- Utilisateurs: $($dashboardResponse.data.statistiques.utilisateurs.total)" -ForegroundColor White
                Write-Host "- Élèves: $($dashboardResponse.data.statistiques.eleves.total)" -ForegroundColor White
                Write-Host "- Cartes: $($dashboardResponse.data.statistiques.cartes.total)" -ForegroundColor White
                Write-Host "`n✅ L'API fonctionne correctement!" -ForegroundColor Green
            } else {
                Write-Host "❌ Erreur: $($dashboardResponse.message)" -ForegroundColor Red
            }
        } catch {
            Write-Host "❌ Erreur lors de l'appel API dashboard:" -ForegroundColor Red
            Write-Host $_.Exception.Message -ForegroundColor Red
            if ($_.ErrorDetails.Message) {
                Write-Host "Détails: $($_.ErrorDetails.Message)" -ForegroundColor Red
            }
        }
    } else {
        Write-Host "❌ Login échoué: $($loginResponse.message)" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ Erreur lors du login:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "Détails: $($_.ErrorDetails.Message)" -ForegroundColor Red
    }
}
