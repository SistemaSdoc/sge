import MultipleSelector from "@/components/ui/multiselect"

export default function MultiSelectorField({
  items = [],
  value = [],
  onChange,
  placeholder = "Selecione...",
  isLoading = false
}) {

  const resolvedPlaceholder = isLoading ? "A carregar..." : !items.length ? "Nenhuma opção disponível" : placeholder

  return (
    <MultipleSelector
      value={value}
      options={items}
      placeholder={resolvedPlaceholder}
      disabled={isLoading || !items.length}
      hideClearAllButton
      hidePlaceholderWhenSelected
      onChange={(val) => onChange(val)}
    />
  )
}