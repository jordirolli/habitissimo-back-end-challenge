<?php

namespace AppBundle\Entity;

use AppBundle\Util\Type\BasicEnum;

abstract class InvoiceState extends BasicEnum {
    const Pending = 0;
    const Published = 1;
    const Discarded = 2;
}