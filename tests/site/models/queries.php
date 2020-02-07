<?php
declare(strict_types=1);

class QueriesPage extends Page implements \Bnomei\HandlebarsData
{
    public function handlebarsData(): array
    {
        return [
            'toppings' => [
                ['name' => 'cheese'],
                ['name' => 'salat'],
                ['name' => 'ketchup'],
                ['name' => 'onions'],
            ],

            'k3v' => '{{ kirby.version }}',
            'count' => '{{ site.index.count }}', // query to execute

            'textWithQuery' => "Some field value {{ page.myfield }} at {{ page.date.toDate('c') }}",
            'kirbytextWithQuery' => $this->text()->kirbytext(),
        ];
    }
}
