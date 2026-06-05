import { Link } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { LucideIcon } from "lucide-react";

interface Action {
  label: string;
  href?: string;
  onClick?: () => void;
  variant?: "default" | "outline" | "ghost" | "destructive" | "secondary" | "link";
}

interface EmptyStateProps {
  icon?: LucideIcon;
  title?: string;
  description?: string;
  action?: Action;
  secondaryActions?: Action[];
  variant?: "default" | "compact" | "minimal" | "table";
  className?: string;
}

export function EmptyState({
  icon: Icon,
  title = "Nenhum dado disponível",
  description = "Parece que ainda não há nada por aqui",
  action,
  secondaryActions = [],
  variant = "default",
  className = "",
}: EmptyStateProps) {
  const hasAction = action && (action.href || action.onClick);

  const containerClasses = {
    default: "py-16 gap-6",
    compact: "py-8 gap-4",
    minimal: "py-4 gap-3",
    table: "py-12 gap-4",
  };

  const iconSizeClasses = {
    default: "size-6",
    compact: "size-5",
    minimal: "size-4",
    table: "size-5",
  };

  const titleClasses = {
    default: "text-base font-semibold",
    compact: "text-sm font-medium",
    minimal: "text-xs font-medium",
    table: "text-sm font-medium",
  };

  const descriptionClasses = {
    default: "text-sm",
    compact: "text-xs",
    minimal: "text-xs",
    table: "text-xs",
  };

  const buttonSize = variant === "minimal" ? "xs" : "sm";

  return (
    <div
      className={`flex flex-col items-center justify-center text-center ${containerClasses[variant]} ${className}`}
    >
      {Icon && (
        <div className="flex flex-col items-center gap-3">
          <div className="rounded-full bg-muted p-3 text-primary/80">
            <Icon className={iconSizeClasses[variant]} strokeWidth={1.5} />
          </div>
          <div className="flex flex-col gap-1.5">
            <p className={titleClasses[variant]}>{title}</p>
            <p className={`${descriptionClasses[variant]} text-muted-foreground`}>
              {description}
            </p>
          </div>
        </div>
      )}

      {(hasAction || secondaryActions.length > 0) && (
        <div className="flex flex-col items-center gap-3 mt-1">
          {hasAction && (
            action?.href ? (
              <Button asChild variant={action.variant ?? "default"} size={buttonSize}>
                <Link href={action.href}>{action.label}</Link>
              </Button>
            ) : (
              <Button onClick={action?.onClick} variant={action?.variant ?? "default"} size={buttonSize}>
                {action?.label}
              </Button>
            )
          )}

          {secondaryActions.length > 0 && (
            <div className="flex flex-wrap items-center justify-center gap-2">
              {secondaryActions.map((secondary, idx) => (
                <Button key={idx} asChild variant="ghost" size={buttonSize} className="text-muted-foreground hover:text-foreground">
                  <Link href={secondary.href!}>{secondary.label}</Link>
                </Button>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}