import { ResumoCardItem } from './item';

export function ResumoCards({ metrics }) {
  if (!metrics) return null;

  return (
    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
      {Array.from(2).map(({}) => (
        <ResumoCardItem />
      ))}
    </div>
  );
}
