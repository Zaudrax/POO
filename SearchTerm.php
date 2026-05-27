<?php

declare(strict_types=1);

final class SearchTerm implements Stringable, JsonSerializable
{
    public function __construct(private string $value)
    {
        $this->value = trim($this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): mixed
    {
        return ['term' => $this->value];
    }
}
