export default function AppLogoIcon(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg
      viewBox="0 0 240 320"
      xmlns="http://www.w3.org/2000/svg"
      role="img"
      style={{ width: '100%', height: '100%' }}
      {...props}
    >
      <title>Logo PGE - P</title>
      <desc>Letra P formada por células</desc>

      {/* Linha 1 */}
      <rect
        x="30"
        y="30"
        width="55"
        height="55"
        fill="currentColor"
        opacity="0.9"
      />
      <rect
        x="95"
        y="30"
        width="55"
        height="55"
        fill="currentColor"
        opacity="0.9"
      />
      <rect
        x="160"
        y="30"
        width="55"
        height="55"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.5"
        opacity="0.6"
      />

      {/* Linha 2 */}
      <rect
        x="30"
        y="100"
        width="55"
        height="55"
        fill="currentColor"
        opacity="0.9"
      />
      <rect
        x="95"
        y="100"
        width="55"
        height="55"
        fill="currentColor"
        opacity="0.9"
      />
      <rect
        x="160"
        y="100"
        width="55"
        height="55"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.5"
        opacity="0.6"
      />

      {/* Linha 3 */}
      <rect
        x="30"
        y="170"
        width="55"
        height="55"
        fill="currentColor"
        opacity="0.9"
      />

      {/* Linha 4 */}
      <rect
        x="30"
        y="240"
        width="55"
        height="55"
        fill="currentColor"
        opacity="0.9"
      />
    </svg>
  );
}
