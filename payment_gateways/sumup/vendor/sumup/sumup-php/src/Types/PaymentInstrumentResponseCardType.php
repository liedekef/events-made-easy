<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Issuing card network of the payment card used for the transaction.
 */
enum PaymentInstrumentResponseCardType: string
{
    case ALELO = 'ALELO';
    case AMEX = 'AMEX';
    case BANCOMAT = 'BANCOMAT';
    case CONECS = 'CONECS';
    case CUP = 'CUP';
    case DINERS = 'DINERS';
    case DISCOVER = 'DISCOVER';
    case EFTPOS = 'EFTPOS';
    case ELO = 'ELO';
    case ELV = 'ELV';
    case GIROCARD = 'GIROCARD';
    case HIPERCARD = 'HIPERCARD';
    case INTERAC = 'INTERAC';
    case JCB = 'JCB';
    case MAESTRO = 'MAESTRO';
    case MASTERCARD = 'MASTERCARD';
    case PLUXEE = 'PLUXEE';
    case SUMUP = 'SUMUP';
    case SWILE = 'SWILE';
    case TICKET = 'TICKET';
    case VISA = 'VISA';
    case VISA_ELECTRON = 'VISA_ELECTRON';
    case VISA_VPAY = 'VISA_VPAY';
    case VPAY = 'VPAY';
    case VR = 'VR';
    case UNKNOWN = 'UNKNOWN';
}
