<?php

use Edc\Core\Support\HtmlSanitizer;

// Unidad pura (sin HTTP): saneador de texto rico (DC-09). Cubre la lista
// blanca ampliada con tablas, `h5` y `width`/`height` de imagen.
function sanitize(string $html): string
{
    return (new HtmlSanitizer)->clean($html) ?? '';
}

it('conserva la tabla completa con clases y colspan, SIN aplanar tr/td', function () {
    $html = '<table><tbody>'
        .'<tr class="green-bg"><th colspan="2">Cabecera</th></tr>'
        .'<tr><td>A</td><td rowspan="2">B</td></tr>'
        .'</tbody></table>';

    $out = sanitize($html);

    expect($out)->toContain('<table>')
        ->and($out)->toContain('<tbody>')
        ->and($out)->toContain('<tr class="green-bg">')
        ->and($out)->toContain('<th colspan="2">Cabecera</th>')
        ->and($out)->toContain('<td>A</td>')
        ->and($out)->toContain('<td rowspan="2">B</td>');
});

it('quita el atributo style, dentro y fuera de una tabla', function () {
    $html = '<p style="color:red">Hola</p>'
        .'<table><tbody><tr><td style="width:10px">A</td></tr></tbody></table>';

    $out = sanitize($html);

    expect($out)->not->toContain('style=')
        ->and($out)->toContain('<td>A</td>');
});

it('quita <script> y atributos onclick', function () {
    $html = '<p onclick="hack()">Hola <script>alert(1)</script>mundo</p>';

    $out = sanitize($html);

    expect($out)->not->toContain('<script')
        ->and($out)->not->toContain('onclick')
        ->and($out)->toContain('Hola')
        ->and($out)->toContain('mundo');
});

it('limpia una etiqueta no permitida DENTRO de una tabla sin romper su estructura', function () {
    $html = '<table><tbody><tr><td><marquee>Ojo</marquee> texto</td><td>B</td></tr></tbody></table>';

    $out = sanitize($html);

    expect($out)->toContain('<table>')
        ->and($out)->toContain('<tr>')
        ->and($out)->not->toContain('<marquee')
        ->and($out)->toContain('Ojo')
        ->and($out)->toContain('<td>B</td>');
});

it('conserva width/height de la imagen pero le quita el style', function () {
    $html = '<img src="/x.png" width="24" height="24" style="border:1px solid" class="rt-icon">';

    $out = sanitize($html);

    expect($out)->toContain('width="24"')
        ->and($out)->toContain('height="24"')
        ->and($out)->not->toContain('style=');
});

it('permite h5 (antes se quedaba fuera)', function () {
    expect(sanitize('<h5>Subtítulo</h5>'))->toContain('<h5>Subtítulo</h5>');
});

it('conserva listas anidadas (ul/ol dentro de li)', function () {
    $html = '<ul><li>Uno<ul><li>Uno.uno</li></ul></li><li>Dos</li></ul>';

    $out = sanitize($html);

    expect($out)->toContain('<li>Uno<ul><li>Uno.uno</li></ul></li>')
        ->and($out)->toContain('<li>Dos</li>');
});
