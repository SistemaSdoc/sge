import { FieldDescription, FieldGroup } from '@/components/ui/field';

export default function FormSection({ id, title, description, children }) {
  return (
    <section
      aria-labelledby={`${id}-title`}
      className="grid grid-cols-1 items-start gap-x-10 gap-y-3 md:grid-cols-3 md:gap-y-0"
    >
      <div className="grid content-start gap-1 md:col-start-1 md:row-span-2 md:row-start-1">
        <h2 id={`${id}-title`} className="text-sm leading-5 font-semibold">
          {title}
        </h2>

        <FieldDescription className="leading-5">{description}</FieldDescription>
      </div>

      <FieldGroup className="gap-5 md:col-span-2 md:col-start-2 md:row-span-2 md:row-start-1">
        <div className="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
          {children}
        </div>
      </FieldGroup>
    </section>
  );
}
