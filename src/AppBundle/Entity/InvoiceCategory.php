<?php

namespace AppBundle\Entity;

use AppBundle\Util\Type\BasicEnum;

abstract class InvoiceCategory extends BasicEnum {
    const Construction = 'construction';
    const Reform = 'reform';
    const Installation = 'installation';
}