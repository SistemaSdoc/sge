export function Spinner({ className = 'size-4' }) {
  const strokes = 8;
  const duration = 0.8;

  return (
    <>
      <style>{`
        @keyframes spinner-fade {
          0%   { opacity: 1; }
          100% { opacity: 0.15; }
        }
      `}</style>
      <svg
        className={className}
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        {Array.from({ length: strokes }).map((_, i) => (
          <line
            key={i}
            x1="12"
            y1="2.5"
            x2="12"
            y2="6"
            stroke="currentColor"
            strokeWidth="2.5"
            strokeLinecap="square"
            transform={`rotate(${i * (360 / strokes)} 12 12)`}
            style={{
              animationName: 'spinner-fade',
              animationDuration: `${duration}s`,
              animationTimingFunction: 'linear',
              animationIterationCount: 'infinite',
              animationDelay: `${-((strokes - i) / strokes) * duration}s`,
            }}
          />
        ))}
      </svg>
    </>
  );
}
