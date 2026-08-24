{{-- Impresión PROPIA del bloque rejilla de personajes (BlockType::pdfView):
     en el papel la rejilla se convierte en una tabla sencilla de atributos —
     la demo del gancho del motor para bloques de datos con impresión
     especial. Recibe $s (settings localizados), $data (resolveData, como el
     render público), $locale, $assets y los helpers de la plantilla. --}}
<div class="block block--characters-grid">
    <div class="block__lead">
        @if (! blank($s['title'] ?? null))
            <{{ $hTitle }}{!! $styleAttr($headingAlign($s, 'title_align')) !!}>{{ $s['title'] }}</{{ $hTitle }}>
        @endif
    </div>
    @if (($data['characters'] ?? []) !== [])
        <table>
            <thead>
                <tr><th>Nombre</th><th>Coste</th><th>Poder</th><th>Prestigio</th><th>Intriga</th><th>Dinero</th></tr>
            </thead>
            <tbody>
                @foreach ($data['characters'] as $character)
                    <tr>
                        <td>{{ $character['name'][$locale] ?? (array_values(array_filter($character['name'] ?? []))[0] ?? '') }}</td>
                        <td>{{ $character['cost'] }}</td>
                        <td>{{ $character['power'] }}</td>
                        <td>{{ $character['prestige'] }}</td>
                        <td>{{ $character['intrigue'] }}</td>
                        <td>{{ $character['money'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
