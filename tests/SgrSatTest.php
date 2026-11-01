<?php

use NFePHP\SgrSat\SgrSat;
use PHPUnit\Framework\TestCase;

class SgrSatTest extends TestCase
{
    /**
     * consulta() is declared to receive the SAT serie as int, but str_pad()
     * was called with the raw int argument. As the class declares
     * strict_types=1, ANY call passing the serie as int (the documented way)
     * crashed with "TypeError: str_pad(): Argument #1 ($string) must be of
     * type string, int given". The curl error check also used an undefined
     * variable ($oaperror instead of $soaperror), so communication failures
     * were never signalled and the method crashed later while parsing the
     * response instead of throwing its "Falha de comunicação" exception.
     */
    public function testConsultaWithIntSerieOnFailedConnectionMustThrowCommunicationException()
    {
        // closed local port: connection refused immediately, no external network
        SgrSat::$url = 'http://127.0.0.1:1/CfeConsultarLotes/CfeConsultarLotes.asmx';
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Falha de comunicação');
        SgrSat::consulta(900006, '2024-01-01', '2024-01-31', 'chave-de-seguranca');
    }
}
