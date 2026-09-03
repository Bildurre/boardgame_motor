<?php

use Edc\Core\Content\Fields\Field;

// DSL de campos: visibilidad condicional (Field::visibleWhen) — el admin
// solo pinta el campo cuando otro del esquema vale lo declarado; la
// validación y el guardado no cambian.

it('serializa la condición de visibilidad del campo', function () {
    $origin = Field::select('origin', ['all' => 'Todos', 'community' => 'Comunidad'])
        ->visibleWhen('section', 'decks');

    expect($origin->toArray()['visible_when'])->toBe(['field' => 'section', 'values' => ['decks']])
        ->and(Field::text('title')->toArray()['visible_when'])->toBeNull();

    // Varios valores admitidos.
    $multi = Field::boolean('narrow')->visibleWhen('section', ['cards', 'heroes']);
    expect($multi->toArray()['visible_when']['values'])->toBe(['cards', 'heroes']);
});

it('la validación del campo condicionado no cambia', function () {
    $field = Field::select('origin', ['all' => 'Todos', 'community' => 'Comunidad'])
        ->visibleWhen('section', 'decks');

    expect($field->rules(['es']))->toBe(['settings.origin' => ['nullable', 'string', 'in:all,community']]);
});
