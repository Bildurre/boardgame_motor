<?php

namespace Database\Seeders;

use App\Models\Character;
use App\Models\House;
use App\Models\Scheme;
use Illuminate\Database\Seeder;

/**
 * Contenido demo del JUEGO: casas con argucias y personajes, publicados y
 * con traducciones. Idempotente: si ya hay casas, no toca nada.
 */
class GameSeeder extends Seeder
{
    public function run(): void
    {
        if (House::count() > 0) {
            return;
        }

        $houses = [
            ['name' => ['es' => 'Casa Lannister', 'eu' => 'Lannister Etxea', 'en' => 'House Lannister'], 'color' => '#a41c1c',
             'schemes' => [
                 ['title' => ['es' => 'Oro y promesas', 'en' => 'Gold and promises'], 'cost' => 3,
                  'description' => ['es' => '<p>Paga 3 de oro: gana 2 de <strong>prestigio</strong>.</p>', 'en' => '<p>Pay 3 gold: gain 2 <strong>prestige</strong>.</p>']],
                 ['title' => ['es' => 'Deuda saldada', 'en' => 'A debt repaid'], 'cost' => 5,
                  'description' => ['es' => '<p>Un Lannister siempre paga sus deudas: roba 2 cartas.</p>']],
             ]],
            ['name' => ['es' => 'Casa Stark', 'eu' => 'Stark Etxea', 'en' => 'House Stark'], 'color' => '#5a6e7f',
             'schemes' => [
                 ['title' => ['es' => 'Se acerca el invierno', 'en' => 'Winter is coming'], 'cost' => 2,
                  'description' => ['es' => '<p>Tus personajes ganan +1 de <strong>poder</strong> este turno.</p>']],
             ]],
            ['name' => ['es' => 'Casa Targaryen', 'eu' => 'Targaryen Etxea', 'en' => 'House Targaryen'], 'color' => '#7a1f3d',
             'schemes' => [
                 ['title' => ['es' => 'Fuego y sangre', 'en' => 'Fire and blood'], 'cost' => 6,
                  'description' => ['es' => '<p>Descarta una carta enemiga en juego.</p>']],
             ]],
        ];

        foreach ($houses as $data) {
            $house = new House;
            $house->setTranslations('name', $data['name']);
            $house->color = $data['color'];
            $house->is_published = true;
            $house->save();

            foreach ($data['schemes'] as $schemeData) {
                $scheme = new Scheme;
                $scheme->house_id = $house->id;
                $scheme->setTranslations('title', $schemeData['title']);
                $scheme->setTranslations('description', $schemeData['description']);
                $scheme->cost = $schemeData['cost'];
                $scheme->is_published = true;
                $scheme->save();
            }
        }

        $characters = [
            ['name' => ['es' => 'Tyrion Lannister'], 'power' => 2, 'prestige' => 4, 'intrigue' => 5, 'money' => 3,
             'ability' => ['es' => '<p><strong>Intriga:</strong> roba 2 cartas al entrar en juego.</p>'],
             'description' => ['es' => '<p>Enano de la casa Lannister, la mente más aguda de Poniente.</p>']],
            ['name' => ['es' => 'Arya Stark'], 'power' => 3, 'prestige' => 1, 'intrigue' => 4, 'money' => 0,
             'ability' => ['es' => '<p><strong>Sigilo:</strong> no puede ser bloqueada el turno que ataca.</p>'],
             'description' => ['es' => '<p>Una chica no es nadie.</p>']],
            ['name' => ['es' => 'Daenerys Targaryen'], 'power' => 5, 'prestige' => 3, 'intrigue' => 2, 'money' => 1,
             'ability' => ['es' => '<p><strong>Dragones:</strong> +2 de poder si controlas otra carta Targaryen.</p>'],
             'description' => ['es' => '<p>Madre de dragones, rompedora de cadenas.</p>']],
        ];

        foreach ($characters as $data) {
            $character = new Character;
            $character->setTranslations('name', $data['name']);
            $character->setTranslations('ability', $data['ability']);
            $character->setTranslations('description', $data['description']);
            $character->power = $data['power'];
            $character->prestige = $data['prestige'];
            $character->intrigue = $data['intrigue'];
            $character->money = $data['money'];
            $character->is_published = true;
            $character->save();
        }
    }
}
