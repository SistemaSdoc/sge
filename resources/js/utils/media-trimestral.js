export function mediaTrimestral(mac, npp, npt) {
  const m = parseFloat(mac)
  const p = parseFloat(npp)
  const t = parseFloat(npt)

  if (isNaN(m) || isNaN(p) || isNaN(t)) return null
  if (mac === "" || npp === "" || npt === "") return null

  const media = (m + p + t) / 3
  return Math.round(media * 2) / 2
}