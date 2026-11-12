# Script de Sincronização Automática com o GitHub / Render
$ErrorActionPreference = "SilentlyContinue"
git add .
$status = git status --porcelain
if ($status) {
    git commit -m "Atualizacao automatica do projeto RoboClean"
    git push origin main
    Write-Output "Alteracoes enviadas com sucesso para o GitHub e Render!"
} else {
    Write-Output "Nenhuma alteracao pendente para envio."
}
