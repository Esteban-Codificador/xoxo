<?php

namespace App\Domain\Content\RichContent;

/**
 * Validates a RichContent envelope ({version, doc}) against the allowlist in
 * RichContentSchema. Returns every error found, each prefixed with the JSON
 * path of the offending node.
 */
final class RichContentValidator
{
    /** @var list<string> */
    private array $errors = [];

    /**
     * @return list<string>
     */
    public function errors(mixed $envelope): array
    {
        $this->errors = [];

        if (! is_array($envelope)) {
            return ['El contenido debe ser un objeto {version, doc}.'];
        }

        if (($envelope['version'] ?? null) !== RichContent::CURRENT_VERSION) {
            $this->errors[] = 'version: se esperaba '.RichContent::CURRENT_VERSION.'.';
        }

        $unknownKeys = array_diff(array_keys($envelope), ['version', 'doc']);
        if ($unknownKeys !== []) {
            $this->errors[] = 'claves no permitidas en el sobre: '.implode(', ', $unknownKeys).'.';
        }

        $encoded = json_encode($envelope);
        if ($encoded === false || strlen($encoded) > RichContentSchema::MAX_BYTES) {
            $this->errors[] = 'el documento supera el tamaño máximo de '.RichContentSchema::MAX_BYTES.' bytes.';

            return $this->errors;
        }

        $doc = $envelope['doc'] ?? null;

        if (! is_array($doc) || ($doc['type'] ?? null) !== 'doc') {
            $this->errors[] = 'doc: el nodo raíz debe ser de tipo "doc".';

            return $this->errors;
        }

        $this->validateNode($doc, 'doc', 0);

        return $this->errors;
    }

    public function passes(mixed $envelope): bool
    {
        return $this->errors($envelope) === [];
    }

    /**
     * @param  array<mixed>  $node
     */
    private function validateNode(array $node, string $path, int $depth): void
    {
        if ($depth > RichContentSchema::MAX_DEPTH) {
            $this->errors[] = "{$path}: se supera la profundidad máxima de ".RichContentSchema::MAX_DEPTH.'.';

            return;
        }

        $type = $node['type'] ?? null;
        $children = RichContentSchema::children();

        if (! is_string($type) || ! array_key_exists($type, $children)) {
            $this->errors[] = "{$path}: tipo de nodo no permitido \"".(is_string($type) ? $type : gettype($type)).'".';

            return;
        }

        $unknownKeys = array_diff(array_keys($node), ['type', 'attrs', 'content', 'text', 'marks']);
        if ($unknownKeys !== []) {
            $this->errors[] = "{$path}: claves no permitidas: ".implode(', ', $unknownKeys).'.';
        }

        $this->validateAttributes($type, $node['attrs'] ?? null, "{$path}.attrs");

        if ($type === 'text') {
            $this->validateText($node, $path);

            return;
        }

        if (isset($node['text']) || isset($node['marks'])) {
            $this->errors[] = "{$path}: solo los nodos de texto pueden tener \"text\" o \"marks\".";
        }

        $this->validateChildren($type, $children[$type], $node['content'] ?? null, $path, $depth);
    }

    /**
     * @param  string|list<string>|null  $allowed
     */
    private function validateChildren(string $type, string|array|null $allowed, mixed $content, string $path, int $depth): void
    {
        if ($content === null) {
            return;
        }

        if ($allowed === null) {
            $this->errors[] = "{$path}: el nodo \"{$type}\" no admite contenido.";

            return;
        }

        if (! is_array($content) || ! array_is_list($content)) {
            $this->errors[] = "{$path}.content: debe ser una lista.";

            return;
        }

        $allowedTypes = is_array($allowed) ? $allowed : match ($allowed) {
            'block' => RichContentSchema::BLOCK_NODES,
            'inline' => RichContentSchema::INLINE_NODES,
            default => ['text'],
        };

        foreach ($content as $index => $child) {
            $childPath = "{$path}.content[{$index}]";

            if (! is_array($child)) {
                $this->errors[] = "{$childPath}: debe ser un nodo.";

                continue;
            }

            $childType = $child['type'] ?? null;

            if (is_string($childType) && ! in_array($childType, $allowedTypes, true)) {
                $this->errors[] = "{$childPath}: \"{$childType}\" no puede ir dentro de \"{$type}\".";

                continue;
            }

            if ($allowed === 'text' && isset($child['marks'])) {
                $this->errors[] = "{$childPath}: el texto de un bloque de código no admite marcas.";
            }

            $this->validateNode($child, $childPath, $depth + 1);
        }
    }

    /**
     * @param  array<mixed>  $node
     */
    private function validateText(array $node, string $path): void
    {
        if (! is_string($node['text'] ?? null) || $node['text'] === '') {
            $this->errors[] = "{$path}: un nodo de texto necesita \"text\" no vacío.";
        }

        if (isset($node['content'])) {
            $this->errors[] = "{$path}: un nodo de texto no admite \"content\".";
        }

        $marks = $node['marks'] ?? [];

        if (! is_array($marks) || ! array_is_list($marks)) {
            $this->errors[] = "{$path}.marks: debe ser una lista.";

            return;
        }

        foreach ($marks as $index => $mark) {
            $markPath = "{$path}.marks[{$index}]";
            $markType = is_array($mark) ? ($mark['type'] ?? null) : null;

            if (! is_string($markType) || ! in_array($markType, RichContentSchema::MARKS, true)) {
                $this->errors[] = "{$markPath}: marca no permitida.";

                continue;
            }

            $this->validateAttributes($markType, $mark['attrs'] ?? null, "{$markPath}.attrs");
        }
    }

    private function validateAttributes(string $type, mixed $attrs, string $path): void
    {
        $rules = RichContentSchema::attributes()[$type] ?? [];

        if ($attrs === null) {
            $attrs = [];
        }

        if (! is_array($attrs)) {
            $this->errors[] = "{$path}: debe ser un objeto.";

            return;
        }

        foreach (array_diff(array_keys($attrs), array_keys($rules)) as $unknown) {
            $this->errors[] = "{$path}.{$unknown}: atributo no permitido en \"{$type}\".";
        }

        foreach ($rules as $name => [$required, $validate]) {
            if (! array_key_exists($name, $attrs)) {
                if ($required) {
                    $this->errors[] = "{$path}.{$name}: atributo obligatorio.";
                }

                continue;
            }

            $error = $validate($attrs[$name]);

            if ($error !== null) {
                $this->errors[] = "{$path}.{$name}: {$error}.";
            }
        }
    }
}
