<?php

$jugador = "Diego";
$puntos = 120;
echo "Hola, $jugador. Tienes $puntos puntos.\n";

$colores = ["rojo", "verde", "azul" , "amarillo"];
echo "Primer color: " . $colores[0] . "\n";
echo "Total de colores: " . count($colores) . "\n";

$partida = [
    "jugador" => "Diego",
    "puntos" => 120,
    "aciertos" => 8,
];
echo "Aciertos: " . $partida["aciertos"] . "\n";

foreach ($partida as $clave => $valor) {
    echo "- $clave: $valor\n";
}

function calcularPuntos(int $aciertos, int $segundos): int
{
    return max(0, $aciertos * 10 - $segundos);
}
echo "Puntos calculados: " . calcularPuntos(6, 20) . "\n";

$dobles = array_map(fn($n) => $n * 2, [1, 2, 3]);
echo "Dobles: " . implode(", ", $dobles) . "\n";

class Puntuacion
{
    public function __construct(
        public string $jugador,
        public int $puntos,
    ) {}

    public function esRecord(int $record): bool
    {
        return $this->puntos > $record;
    }
}

$p = new Puntuacion("Diego", 150);
echo $p->jugador . " " . ($p->esRecord(120) ? "ha batido el record" : "no ha batido el record") . "\n";

$p2 = new Puntuacion("Maria", 250);
echo $p2->jugador . " " . ($p2->esRecord(250) ? "ha batido el record" : "no ha batido el record") . "\n";

$json = json_encode($partida);
echo "JSON: $json\n";
$deVuelta = json_decode($json, true);
echo "Jugador desde JSON: " . $deVuelta["jugador"] . "\n";