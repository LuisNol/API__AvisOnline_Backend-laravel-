$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE3MzM2MzkwMDIsImV4cCI6MTczMzcyNTQwMiwibmJmIjoxNzMzNjM5MDAyLCJqdGkiOiJFQnF5RHdKQVJKWUZXVXVnIiwic3ViIjoiMSIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.nM-xOF5ULOBOGAglMTqtUBg-g8pE_N5Xjz0ky_BqE6o"

$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
    "X-User-Permission" = "manage-products"
}

Write-Host "Probando API de categorias..."
try {
    $response = Invoke-RestMethod -Uri "http://localhost:8000/api/admin/categories?page=1&search=" -Method GET -Headers $headers
    Write-Host "Respuesta exitosa:"
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error:"
    Write-Host $_.Exception.Message
    Write-Host "Status Code: $($_.Exception.Response.StatusCode)"
} 