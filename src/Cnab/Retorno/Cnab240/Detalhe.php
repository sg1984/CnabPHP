<?php
namespace Cnab\Retorno\Cnab240;

class Detalhe extends \Cnab\Format\Linha implements \Cnab\Retorno\IDetalhe
{
    public $codigo_banco;
    public $arquivo;

    public $segmento_t;
    public $segmento_u;
    public $segmento_w;

	public function __construct(\Cnab\Retorno\IArquivo $arquivo)
	{
		$this->codigo_banco = $arquivo->codigo_banco;
        $this->arquivo = $arquivo;
	}
	
	/**
	 * Retorno se é para dar baixa no boleto
	 * @return Boolean
	 */
	public function isBaixa($forcadaBanco = false)
    {
        $codigo_movimento = $this->segmento_t->codigo_movimento;
	    return self::isBaixaStatic($codigo_movimento, $forcadaBanco);
	}

	public static function isBaixaStatic($codigo_movimento, $forcadaBanco = false)
	{
	    if( $forcadaBanco ){
//            9  => 'Baixa'
//            25 => 'Protestado e Baixado (Baixa por Ter Sido Protestado)'
            $tipo_baixa = array(9, 25);
        }
        else{
//            6  => 'Liquidação'
//            17 => 'Liquidação Após Baixa ou Liquidação Título Não Registrado'
            $tipo_baixa = array(6, 17);
        }

		$codigo_movimento = (int)$codigo_movimento;
		if(in_array($codigo_movimento, $tipo_baixa))
			return true;
		else
			return false;
	}

	/**
	 * Retorno se é uma baixa rejeitada
	 * @return Boolean
	 */
	public function isBaixaRejeitada()
	{
		$tipo_baixa = array(3, 26, 30);
		$codigo_movimento = (int)$this->segmento_t->codigo_movimento;
		if(in_array($codigo_movimento, $tipo_baixa))
			return true;
		else
			return false;
	}

	/**
	 * Identifica o tipo de detalhe, se por exemplo uma taxa de manutenção
	 * @return Integer
	 */
	public function getCodigo()
	{
		return (int)$this->segmento_t->codigo_movimento;
	}
	
	/**
	 * Retorna o valor recebido em conta
	 * @return Double
	 */
	public function getValorRecebido()
	{
		return $this->segmento_u->valor_liquido;
	}

	/**
	 * Retorna o valor do título
	 * @return Double
	 */
	public function getValorTitulo()
	{
		return $this->segmento_t->valor_titulo;
	}

	/**
	 * Retorna o valor do pago
	 * @return Double
	 */
	public function getValorPago()
	{
		return $this->segmento_u->valor_pago;
	}

	/**
	 * Retorna o valor da tarifa
	 * @return Double
	 */
	public function getValorTarifa()
	{
		return $this->segmento_t->valor_tarifa;
	}

	/**
	 * Retorna o valor do Imposto sobre operações financeiras
	 * @return Double
	 */
	public function getValorIOF()
	{
		return $this->segmento_u->valor_iof;
	}

	/**
	 * Retorna o valor dos descontos concedido (antes da emissão)
	 * @return Double;
	 */
	public function getValorDesconto()
	{
		return $this->segmento_u->valor_desconto;
	}

	/**
	 * Retorna o valor dos abatimentos concedidos (depois da emissão)
	 * @return Double
	 */
	public function getValorAbatimento()
	{
		return $this->segmento_u->valor_abatimento;
	}

	/**
	 * Retorna o valor de outras despesas
	 * @return Double
	 */
	public function getValorOutrasDespesas()
	{
	    return $this->segmento_u->valor_outras_despesas;
	}

	/**
	 * Retorna o valor de outros creditos
	 * @return Double
	 */
	public function getValorOutrosCreditos()
	{
	    return $this->segmento_u->valor_outros_creditos;
	}

	/**
	 * Retorna o número do documento do boleto
	 * @return String
	 */
	public function getNumeroDocumento()
	{
        $numero_documento = $this->segmento_t->numero_documento;
        if(trim($numero_documento, '0') == '')
            return null;
        return $numero_documento;
	}

	/**
	 * Retorna o nosso número do boleto
	 * @return String
	 */
    public function getNossoNumero()
    {
        $nossoNumero = $this->segmento_t->nosso_numero;

        if ($this->codigo_banco == 1) {
            $nossoNumero = preg_replace(
                '/^'.strval($this->arquivo->getCodigoConvenio()).'/',
                '',
                $nossoNumero
            );
        } elseif(in_array($this->codigo_banco, array(\Cnab\Banco::SANTANDER))) {
            // retira o dv
            $nossoNumero = substr($nossoNumero, 0, -1);
        } elseif(in_array($this->codigo_banco, array(\Cnab\Banco::CEF))) {
            if ($nossoNumero > 9999999) {
                $nossoNumero = substr($nossoNumero,-7)+0;
            }
        } elseif(in_array($this->codigo_banco, array(\Cnab\Banco::SICOOB))) {
            // retira o dv
            //Nosso Número:
            //- Se emissão a cargo do Sicoob (vide planilha ""Capa"" deste arquivo): Brancos
            //- Se emissão a cargo do Beneficiário (vide planilha ""Capa"" deste arquivo):
            //     NumTitulo - 10 posições (1 a 10)
            //     Parcela - 02 posições (11 a 12) - ""01"" se parcela única
            //     Modalidade - 02 posições (13 a 14) - vide planilha ""Capa"" deste arquivo
            //     Tipo Formulário - 01 posição  (15 a 15):
            //          ""1"" -auto-copiativo
            //          ""3""-auto-envelopável
            //          ""4""-A4 sem envelopamento
            //          ""6""-A4 sem envelopamento 3 vias
            //     Em branco - 05 posições (16 a 20)"
            $nossoNumero = substr($nossoNumero, 0, -6);
        }

        return $nossoNumero;
    }

	/**
	 * Retorna o objeto \DateTime da data de vencimento do boleto
	 * @return \DateTime
	 */
	public function getDataVencimento()
	{
		$data = $this->segmento_t->data_vencimento ? \DateTime::createFromFormat('dmY', sprintf('%08d', $this->segmento_t->data_vencimento)) : false;
        if($data)
            $data->setTime(0,0,0);
        return $data;        
	}

	/**
	 * Retorna a data em que o dinheiro caiu na conta
	 * @return \DateTime
	 */
	public function getDataCredito()
	{
		$data = $this->segmento_u->data_credito ? \DateTime::createFromFormat('dmY', sprintf('%08d', $this->segmento_u->data_credito)) : false;
        if($data)
            $data->setTime(0,0,0);
        return $data;
	}

	/**
	 * Retorna o valor de juros e mora
	 */
	public function getValorMoraMulta()
	{
		return $this->segmento_u->valor_acrescimos;
	}

	/**
	 * Retorna a data da ocorrencia, o dia do pagamento
	 * @return \DateTime
	 */
	public function getDataOcorrencia()
	{
		$data = $this->segmento_u->data_ocorrencia ? \DateTime::createFromFormat('dmY', sprintf('%08d', $this->segmento_u->data_ocorrencia)) : false;
        if($data)
            $data->setTime(0,0,0);
        return $data;
	}

	/**
	 * Retorna o número da carteira do boleto
	 * @return String
	 */
	public function getCarteira()
    {
        if($this->codigo_banco == 104)
        {
            /*
            É formado apenas o código da carteira
            Código da Carteira
            Código adotado pela FEBRABAN, para identificar a característica dos títulos dentro das modalidades de
            cobrança existentes no banco.
            ‘1’ = Cobrança Simples
            ‘3’ = Cobrança Caucionada
            ‘4’ = Cobrança Descontada
            O Código ‘1’ Cobrança Simples deve ser obrigatoriamente informado nas modalidades Cobrança Simples
            e Cobrança Rápida.
            */
            return null;
        }
        else if($this->segmento_t->existField('carteira'))
    		return $this->segmento_t->carteira;
        else
            return null;
            
	}

	/**
	 * Retorna o número da agencia do boleto
	 * @return String
	 */
	public function getAgencia()
	{
		return $this->segmento_t->agencia_mantenedora;
	}

	/**
	 * Retorna o número da agencia do boleto
	 * @return String
	 */
	public function getAgenciaDv()
	{
		return $this->segmento_t->agencia_dv;
	}
	
	/**
	 * Retorna a agencia cobradora
	 * @return string
	 */
	public function getAgenciaCobradora()
	{
		return $this->segmento_t->agencia_cobradora;
	}
	
	/**
	 * Retorna a o dac da agencia cobradora
	 * @return string
	 */
	public function getAgenciaCobradoraDac()
	{
		return $this->segmento_t->agencia_cobradora_dac;
	}
	
	/**
	 * Retorna o numero sequencial
	 * @return Integer;
	 */
	public function getNumeroSequencial()
	{
		return $this->segmento_t->numero_sequencial_lote;
	}

	/**
	 * Retorna o nome do código
	 * @return string
	 */
	public function getCodigoNome()
	{
        $codigo = (int)$this->getCodigo();

        $table = array(
    	     2 => 'Entrada Confirmada',
             3 => 'Entrada Rejeitada',
             4 => 'Transferência de Carteira/Entrada',
             5 => 'Transferência de Carteira/Baixa',
             6 => 'Liquidação',
             7 => 'Confirmação do Recebimento da Instrução de Desconto',
             8 => 'Confirmação do Recebimento do Cancelamento do Desconto',
             9 => 'Baixa',
            11 => 'Títulos em Carteira (Em Ser)',
            12 => 'Confirmação Recebimento Instrução de Abatimento',
            13 => 'Confirmação Recebimento Instrução de Cancelamento Abatimento',
            14 => 'Confirmação Recebimento Instrução Alteração de Vencimento',
            15 => 'Franco de Pagamento',
            17 => 'Liquidação Após Baixa ou Liquidação Título Não Registrado',
            19 => 'Confirmação Recebimento Instrução de Protesto',
            20 => 'Confirmação Recebimento Instrução de Sustação/Cancelamento de Protesto',
            23 => 'Remessa a Cartório (Aponte em Cartório)',
            24 => 'Retirada de Cartório e Manutenção em Carteira',
            25 => 'Protestado e Baixado (Baixa por Ter Sido Protestado)',
            26 => 'Instrução Rejeitada',
            27 => 'Confirmação do Pedido de Alteração de Outros Dados',
            28 => 'Débito de Tarifas/Custas',
            29 => 'Ocorrências do Pagador',
            30 => 'Alteração de Dados Rejeitada',
            33 => 'Confirmação da Alteração dos Dados do Rateio de Crédito',
            34 => 'Confirmação do Cancelamento dos Dados do Rateio de Crédito',
            35 => 'Confirmação do Desagendamento do Débito Automático',
            36 => 'Confirmação de envio de e-mail/SMS',
            37 => 'Envio de e-mail/SMS rejeitado',
            38 => 'Confirmação de alteração do Prazo Limite de Recebimento (a data deve ser',
            39 => 'Confirmação de Dispensa de Prazo Limite de Recebimento',
            40 => 'Confirmação da alteração do número do título dado pelo Beneficiário',
            41 => 'Confirmação da alteração do número controle do Participante',
            42 => 'Confirmação da alteração dos dados do Pagador',
            43 => 'Confirmação da alteração dos dados do Pagadorr/Avalista',
            44 => 'Título pago com cheque devolvido',
            45 => 'Título pago com cheque compensado',
            46 => 'Instrução para cancelar protesto confirmada',
            47 => 'Instrução para protesto para fins falimentares confirmada',
            48 => 'Confirmação de instrução de transferência de carteira/modalidade de cobrança',
            49 => 'Alteração de contrato de cobrança',
            50 => 'Título pago com cheque pendente de liquidação',
            51 => 'Título DDA reconhecido pelo Pagador',
            52 => 'Título DDA não reconhecido pelo Pagador',
            53 => 'Título DDA recusado pela CIP',
            54 => 'Confirmação da Instrução de Baixa de Título Negativado sem Protesto',
            55 => 'Confirmação de Pedido de Dispensa de Multa',
            56 => 'Confirmação do Pedido de Cobrança de Multa',
            57 => 'Confirmação do Pedido de Alteração de Cobrança de Juros',
            58 => 'Confirmação do Pedido de Alteração do Valor/Data de Desconto',
            59 => 'Confirmação do Pedido de Alteração do Beneficiário do Título',
            60 => 'Confirmação do Pedido de Dispensa de Juros de Mora',
            85 => 'Confirmação de Desistência de Protesto',
            86 => 'Confirmação de cancelamento do Protesto',
        );

        if(array_key_exists($codigo, $table))
            return $table[$codigo];
        else
            return 'Desconhecido';
    }

    /**
     * Retorna o código de liquidação, normalmente usado para 
     * saber onde o cliente efetuou o pagamento
     * @return String
     */
    public function getCodigoLiquidacao() {
        // @TODO: Resgatar o código de liquidação
        return null;
    }

    /**
     * Retorna a descrição do código de liquidação, normalmente usado para 
     * saber onde o cliente efetuou o pagamento
     * @return String
     */
    public function getDescricaoLiquidacao() {
        // @TODO: Resgator descrição do código de liquidação
        return null;
    }

    public function dump()
    {
        $dump  = PHP_EOL;
        $dump .= '== SEGMENTO T ==';
        $dump .= PHP_EOL;
        $dump .= $this->segmento_t->dump();
        $dump .= '== SEGMENTO U ==';
        $dump .= PHP_EOL;
        $dump .= $this->segmento_u->dump();

        if ($this->segmento_w)
        {
            $dump .= '== SEGMENTO W ==';
            $dump .= PHP_EOL;
            $dump .= $this->segmento_w->dump();
        }

        return $dump;
    }

    public function isDDA()
    {
        // @TODO: implementar funçao isDDA no Cnab240
    }

    public function getAlegacaoPagador()
    {
        // @TODO: implementar funçao getAlegacaoPagador no Cnab240
    }
}
