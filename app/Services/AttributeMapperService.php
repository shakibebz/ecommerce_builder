<?php

namespace App\Services;

class AttributeMapperService
{
    protected array $mappings;

    public function __construct()
    {
        $this->mappings = config('magento_attributes', []);
    }

    /**
     * Finds the Magento attribute mapping for a given label.
     *
     * @param string $label The Persian label (e.g., "رنگ")
     * @return array|null An array with 'code' and 'type' or null if not found.
     */
    public function findByLabel(string $label): ?array
    {
        return $this->mappings[$label] ?? null;
    }
}
