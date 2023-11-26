<?php

namespace NW\WebService\References\Operations\Notification\Core;

class Contractor
{
    // непонятные свойства у класса, нужно немного больше времени и/или информации, что из них должно задаваться через
    // конструкт, наверняка $id в нем же присваивается $name и $fullName, а type сделать enum или class
    const int TYPE_CUSTOMER = 0;
    private int $id;
    public $type;
    public $name;

    public  static function isExist(int $id): bool
    {
        //какой-то код на проверку существует ли id у сущности
        return true;
    }

    public function getFullName(): string
    {
        return $this->name.' '.$this->id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}