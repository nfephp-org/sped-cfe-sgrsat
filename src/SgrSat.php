<?php

declare(strict_types=1);

namespace NFePHP\SgrSat;

/**
 * Classe para obtenção dos dados de Retaguarda do SGR-SAT SP
 */

class SgrSat
{

    /**
     * @var string
     */
    public static $url = "https://wssatsp.fazenda.sp.gov.br/CfeConsultarLotes/CfeConsultarLotes.asmx";

    /**
     * Recebe parametros e faz a consula
     *
     * @param int $serie
     * @param string $inicial
     * @param string $final
     * @param string $chave
     * @return string
     */
    public static function consulta(int $serie, string $inicial, string $final, string $chave): string
    {
        $dtini = new \DateTime($inicial);
        $dtfim = new \DateTime($final);
        $ini = (string) $dtini->format('dmY') . "000000";
        $fim = (string) $dtfim->format('dmY') . '595900';
        $satserie = str_pad((string) $serie, 9, '0', STR_PAD_LEFT);

        $message = "<?xml version='1.0' encoding='UTF-8'?>"
            . "<consLote xmlns=\"http://www.fazenda.sp.gov.br/sat\" versao=\"0.06\">"
            . "<nserieSAT>{$satserie}</nserieSAT>"
            . "<dhInicial>{$ini}</dhInicial>"
            . "<dhFinal>{$fim}</dhFinal>"
            . "<chaveSeguranca>{$chave}</chaveSeguranca>"
            . "</consLote>";

        $envelope = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" "
            . "xmlns:cfec=\"http://www.fazenda.sp.gov.br/sat/wsdl/CfeConsultaLotes\">"
            . "<soapenv:Header>"
            . "<cfec:cfeCabecMsg>"
            . "<cfec:cUF>35</cfec:cUF>"
            . "<cfec:versaoDados>0.06</cfec:versaoDados>"
            . "</cfec:cfeCabecMsg>"
            . "</soapenv:Header>"
            . "<soapenv:Body>"
            . "<cfec:CfeConsultarLotes>"
            . "<cfec:cfeDadosMsg>"
            . htmlentities($message)
            . "</cfec:cfeDadosMsg>"
            . "</cfec:CfeConsultarLotes>"
            . "</soapenv:Body>"
            . "</soapenv:Envelope>";

        return self::send($envelope);
    }

    /**
     * Envia a requisição SOAP
     *
     * @param string $envelope
     * @return string
     */
    protected static function send(string $envelope): string
    {
        $msgSize = strlen($envelope);
        $header = [
            "Accept-Encoding: gzip,deflate",
            "Content-Type: text/xml;charset=UTF-8",
            "SOAPAction: \"http://www.fazenda.sp.gov.br/sat/wsdl/CfeConsultar\"",
            "Content-length: $msgSize"
        ];

        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_URL, self::$url);
        curl_setopt($oCurl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 40);
        curl_setopt($oCurl, CURLOPT_HEADER, 1);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $envelope);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, $header);
        $response = curl_exec($oCurl);
        $soaperror = curl_error($oCurl);
        $soaperror_code = curl_errno($oCurl);
        $ainfo = curl_getinfo($oCurl);
        if (is_array($ainfo)) {
            $soapinfo = $ainfo;
        }
        $headsize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
        //$httpcode = curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
        curl_close($oCurl);
        if (!empty($soaperror)) {
            throw new \Exception("Falha de comunicação: " . $soaperror_code . ' - ' . $soaperror, $soaperror_code);
        }
        //$responseHead = trim(substr($response, 0, $headsize));
        $responseBody = trim(substr($response, $headsize));
        $dom = new \DOMDocument();
        $dom->loadXML($responseBody);
        $node = $dom->getElementsByTagName('CfeConsultarLotesResult')->item(0);
        if (empty($node)) {
            throw new \Exception('A resposta não contém o elemento CfeConsultarLotesResult.');
        }
        //return $responseBody;
        return $node->textContent;
    }
}
