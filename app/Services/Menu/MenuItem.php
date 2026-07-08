<?php

namespace App\Services\Menu;

use Closure;

final class MenuItem
{
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $href,
        public readonly string $icon,
        public readonly Closure|bool $can = true,
    ) {}

    public function isVisible(): bool
    {
        return $this->can instanceof Closure
            ? (bool) call_user_func($this->can)
            : $this->can;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'href' => $this->href,
            'icon' => $this->icon,
        ];
    }
}
