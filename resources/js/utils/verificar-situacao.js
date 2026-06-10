export function verificarSituacao(mt, faltas) {
  if (mt === null || mt === undefined) return null
  if (faltas >= 8) return "N/APTO"
  return mt >= 10 ? "APTO" : "N/APTO"
}