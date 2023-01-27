<?php

enum Suit
{
    case Hearts;
    case Diamonds;
    case Clubs;
    case Spades;
}

enum BackedSuit: string
{
    case Hearts = 'H';
    case Diamonds = 'D';
    case Clubs = 'C';
    case Spades = 'S';
}

enum Numbers: string {
  case ONE   = '1';
  case TWO   = '2';
  case THREE = '3';
  case FOUR  = '4';

  public function divisibility(): string {
    return match ($this) {
      self::ONE, self::THREE => 'odd',
      self::TWO, self::FOUR => 'even',
    };
  }

  public function foobar(): string {
    return match ($foo) { // undefined variable $foo
      'x' => 'first',
      'y' => 'second',
      default => 'unknown',
    };
  }
}
