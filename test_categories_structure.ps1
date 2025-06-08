# Hacer login para obtener token válido
$loginData = @{
    email = "echodev@gmail.com"
    password = "12345678"
} | ConvertTo-Json

$loginHeaders = @{
    "Content-Type" = "application/json"
}

Write-Host "1. Haciendo login..."
try {
    $loginResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login" -Method POST -Body $loginData -Headers $loginHeaders
    $token = $loginResponse.access_token
    Write-Host "Login exitoso! Token obtenido."
    
    # Probar categorías y mostrar estructura completa
    $headers = @{
        "Authorization" = "Bearer $token"
        "Content-Type" = "application/json"
        "X-User-Permission" = "manage-products"
    }

    Write-Host "2. Probando API de categorias..."
    $response = Invoke-RestMethod -Uri "http://localhost:8000/api/admin/categories?page=1&search=" -Method GET -Headers $headers
    
    Write-Host "3. ESTRUCTURA COMPLETA DE LA RESPUESTA:"
    $response | ConvertTo-Json -Depth 5
    
} catch {
    Write-Host "Error:"
    Write-Host $_.Exception.Message
    if ($_.Exception.Response) {
        Write-Host "Status Code: $($_.Exception.Response.StatusCode)"
    }
} 