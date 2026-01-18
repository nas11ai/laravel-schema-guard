<?php

declare(strict_types=1);

use Nas11ai\SchemaGuard\Domain\ValueObjects\DangerLevel;

test('danger level has correct color', function () {
  expect(DangerLevel::SAFE->getColor())->toBe('green')
    ->and(DangerLevel::LOW->getColor())->toBe('blue')
    ->and(DangerLevel::MEDIUM->getColor())->toBe('yellow')
    ->and(DangerLevel::HIGH->getColor())->toBe('red')
    ->and(DangerLevel::CRITICAL->getColor())->toBe('red');
});

test('danger level has correct description', function () {
  expect(DangerLevel::SAFE->getDescription())->toBe('No dangerous operations detected')
    ->and(DangerLevel::CRITICAL->getDescription())->toContain('Data loss');
});

test('danger level requires confirmation for high and critical', function () {
  expect(DangerLevel::SAFE->requiresConfirmation())->toBeFalse()
    ->and(DangerLevel::LOW->requiresConfirmation())->toBeFalse()
    ->and(DangerLevel::MEDIUM->requiresConfirmation())->toBeFalse()
    ->and(DangerLevel::HIGH->requiresConfirmation())->toBeTrue()
    ->and(DangerLevel::CRITICAL->requiresConfirmation())->toBeTrue();
});

test('danger level has correct priority', function () {
  expect(DangerLevel::SAFE->getPriority())->toBe(0)
    ->and(DangerLevel::LOW->getPriority())->toBe(1)
    ->and(DangerLevel::MEDIUM->getPriority())->toBe(2)
    ->and(DangerLevel::HIGH->getPriority())->toBe(3)
    ->and(DangerLevel::CRITICAL->getPriority())->toBe(4);
});

test('danger level max returns higher level', function () {
  expect(DangerLevel::LOW->max(DangerLevel::HIGH))->toBe(DangerLevel::HIGH)
    ->and(DangerLevel::HIGH->max(DangerLevel::LOW))->toBe(DangerLevel::HIGH)
    ->and(DangerLevel::SAFE->max(DangerLevel::CRITICAL))->toBe(DangerLevel::CRITICAL);
});