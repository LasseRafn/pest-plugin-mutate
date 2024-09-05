<?php

declare(strict_types=1);

use Tests\Fixtures\Classes\AgeHelper;

describe('catches all', function(){
    it('catches all the mutants', function (int $age, bool $isAdult) {
        expect(AgeHelper::isAdult($age))
            ->toBe($isAdult);
    })->with([
        [10, false],
        [17, false],
        [18, true],
        [25, true],
    ]);
});
