<?php

namespace NW\WebService\References\Operations\Notification\Core;

abstract class ReferencesOperation
{
    abstract public function doOperation(): array;


    protected array $data;
    protected int $resellerId;
    protected int $clientId;
    protected int $creatorId;
    protected int $expertId;
    protected int $notificationType;


    public function __construct(string $dataRequest)
    {
        $this->data = $_REQUEST[$dataRequest];
        $this->resellerId = (int) $this->data['resellerId'];
        $this->clientId = (int) $this->data['clientId'];
        $this->creatorId = (int) $this->data['creatorId'];
        $this->expertId = (int) $this->data['expertId'];
        $this->notificationType = (int) $this->data['notificationType'];
        //дальше заполняем данными, что бы заменить все обращения к $data

    }

    function getResellerEmailFrom(): string
    {
        return 'contractor@example.com';
    }

    function getEmailsByPermit($resellerId, $event): array
    {
        // fakes the method
        return ['someemeil@example.com', 'someemeil2@example.com'];
    }
}