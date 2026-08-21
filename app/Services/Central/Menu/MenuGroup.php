<?php

namespace App\Services\Central\Menu;

final class MenuGroup
{
    /** @param MenuItem[] $items */
    public function __construct(
        public readonly string $label,
        public readonly array $items,
    ) {}

    public function toArray(): ?array
    {
        $visible = array_values(array_filter(
            $this->items,
            fn (MenuItem $item) => $item->isVisible()
        ));

        if (empty($visible)) {
            return null; // grupo some inteiro se não sobrar nenhum item
        }

        return [
            'label' => $this->label,
            'items' => array_map(fn (MenuItem $item) => $item->toArray(), $visible),
        ];
    }
}
