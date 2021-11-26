<?php

namespace App\Utils;

/**
 * Classe responsável por controlar JSONs
 */
class JSONFile {
  public function __construct(private string $__file) {
    foreach (json_decode(file_get_contents($__file)) as $k => $v) {
      $this->$k = $v;
    }
  }

  private function encode(): string {
    return json_encode($this, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }

  public function __toString(): string {
    return $this->encode();
  }

  public function save() {
    file_put_contents($this->__file, $this->encode());
  }
}
