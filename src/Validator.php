<?php

namespace Evarmi\IdAccountValidator;

class Validator
{
    public static function documentValidation($document)
    {
        $documento = strtoupper(str_replace([' ', '-', '.'], '', $document));

        if (preg_match('/^[0-9]{8}[A-Z]$/', $documento)) {
            // Es un DNI
            return self::validarDNI($documento);
        } elseif (preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $documento)) {
            // Es un NIE
            return self::validarNIE($documento);
        } elseif (preg_match('/^[ABCDEFGHJNPQRSUVW][0-9]{7}[0-9A-J]$/', $documento)) {
            // Es un CIF
            return self::validarCIF($documento);
        }

        return [
            'type' => 'unknown',
            'value' => false,
        ];
    }

    private static function validarDNI($dni, ?string $type = null)
    {
        $numeros = substr($dni, 0, 8);
        $letra = substr($dni, -1);
        $letrasValidas = 'TRWAGMYFPDXBNJZSQVHLCKE';

        return [
            'type' => $type == 'NIE' ? 'NIE' : 'DNI',
            'value' => $dni,
            'validation' => $letra === $letrasValidas[$numeros % 23],
        ];
    }

    private static function validarNIE($nie)
    {
        $primeraLetra = substr($nie, 0, 1);
        $numeros = substr($nie, 1, 7);
        $letra = substr($nie, -1);

        // Sustituir la letra inicial por su correspondiente número
        switch ($primeraLetra) {
            case 'X':
                $numeros = '0' . $numeros;
                break;
            case 'Y':
                $numeros = '1' . $numeros;
                break;
            case 'Z':
                $numeros = '2' . $numeros;
                break;
            default:
                return false;
        }

        return self::validarDNI($numeros . $letra, 'NIE');
    }

    private static function validarCIF($cif)
    {
        $letra = substr($cif, 0, 1);
        $numeros = substr($cif, 1, 7);
        $control = substr($cif, -1);

        // Cálculo del dígito de control
        $sumaPar = 0;
        $sumaImpar = 0;

        for ($i = 0; $i < 7; $i++) {
            $digito = (int) $numeros[$i];
            if ($i % 2 === 0) {
                // Posiciones impares
                $doble = $digito * 2;
                $sumaImpar += $doble > 9 ? $doble - 9 : $doble;
            } else {
                // Posiciones pares
                $sumaPar += $digito;
            }
        }

        $sumaTotal = $sumaPar + $sumaImpar;
        $digitoControlCalculado = (10 - ($sumaTotal % 10)) % 10;

        if (ctype_alpha($control)) {
            // Control por letra
            $letrasValidas = 'JABCDEFGHI';
            return [
                'type' => 'CIF',
                'value' => $cif,
                'validation' => $control === $letrasValidas[$digitoControlCalculado],
            ];
        } else {
            // Control por número
            return [
                'type' => 'CIF',
                'value' => $cif,
                'validation' => (int) $control === $digitoControlCalculado,
            ];
        }
    }
}
