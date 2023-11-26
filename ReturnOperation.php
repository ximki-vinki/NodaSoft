<?php

namespace NW\WebService\References\Operations\Notification;

use NW\WebService\References\Operations\Notification\Contractors\Employee\Creator;
use NW\WebService\References\Operations\Notification\Contractors\Seller;
use NW\WebService\References\Operations\Notification\Core\ReferencesOperation;
use NW\WebService\References\Operations\Notification\Enums\NotificationEvents;
use NW\WebService\References\Operations\Notification\Enums\Status;

class TsReturnOperation extends ReferencesOperation
{
    private const int TYPE_NEW = 1;
    private const int TYPE_CHANGE = 2;


    /**
     * @throws \Exception
     */
    public function doOperation(): array
    {
        $this->throwError();

        $result = [
            'notificationEmployeeByEmail' => false,
            'notificationClientByEmail'   => false,
            'notificationClientBySms'     => [
                'isSent'  => false,
                'message' => '',
            ],
        ];
        if (empty($this->resellerId)) {
            $result['notificationClientBySms']['message'] = 'Empty resellerId';
            //непонятно, это ошибка или действительно надо сразу отправлять
            return $result;
        }
        $templateData = $this->getTemplateData();

        //Дальше надо рефакторить и править удаляя повторный для MessagesClient::sendMessage и
        // наверное можно $result можно сделать свойством класса, а записывать туда через функции makeTrue

        $emailFrom = getResellerEmailFrom($this->resellerId);
        // Получаем email сотрудников из настроек
        $emails = getEmailsByPermit($this->resellerId, 'tsGoodsReturn');
        if (!empty($emailFrom) && count($emails) > 0) {
            foreach ($emails as $email) {
                MessagesClient::sendMessage([
                    0 => [ // MessageTypes::EMAIL
                        'emailFrom' => $emailFrom,
                        'emailTo'   => $email,
                        'subject'   => __('complaintEmployeeEmailSubject', $templateData,
                            $this->resellerId),
                        'message'   => __('complaintEmployeeEmailBody', $templateData,
                            $this->resellerId),
                    ],
                ], $this->resellerId, NotificationEvents::CHANGE_RETURN_STATUS);
                $result['notificationEmployeeByEmail'] = true;
            }
        }

        // Шлём клиентское уведомление, только если произошла смена статуса
        if ($this->notificationType === self::TYPE_CHANGE && !empty($data['differences']['to'])) {
            if (!empty($emailFrom) && !empty($client->email)) {
                MessagesClient::sendMessage([
                    0 => [ // MessageTypes::EMAIL
                        'emailFrom' => $emailFrom,
                        'emailTo'   => $client->email,
                        'subject'   => __('complaintClientEmailSubject', $templateData,
                            $this->resellerId),
                        'message'   => __('complaintClientEmailBody', $templateData,
                            $this->resellerId),
                    ],
                ], $this->resellerId, $client->id, NotificationEvents::CHANGE_RETURN_STATUS,
                    (int) $data['differences']['to']);
                $result['notificationClientByEmail'] = true;
            }
            //$error непонятно откуда взялся
            if (!empty($client->mobile)) {
                $res = NotificationManager::send($this->resellerId, $client->id,
                    NotificationEvents::CHANGE_RETURN_STATUS, (int) $data['differences']['to'],
                    $templateData, $error);
                if ($res) {
                    $result['notificationClientBySms']['isSent'] = true;
                }
                if (!empty($error)) {
                    $result['notificationClientBySms']['message'] = $error;
                }
            }
        }

        //Не имеет смысла всегда будет выдавать false всегда есть id,
        // в крайнем случае проверку надо выносить в class
        /* if (empty($client->getFullName())) {
             $cFullName = $client->name;
         }*/

        return $result;
    }

    /**
     * @throws \Exception
     */
    private function throwError(): void
    {
        $this->throwErrorNotFound();

        $notificationType = $this->notificationType;
        if ($notificationType == 0) {
            throw new \Exception('Empty notificationType', 400);
        }
        $templateData = $this->getTemplateData();

        foreach ($templateData as $key => $tempData) {
            if (empty($tempData)) {
                throw new \Exception("Template Data ({$key}) is empty!", 500);
            }
        }
    }

    private function throwErrorNotFound(): void
    {
        $resellerId = $this->resellerId;
        if (!Seller::isExist($resellerId)) {
            throw new \Exception('Seller not found!', 400);
        }

        $clientId = $this->clientId;
        if (!Seller::isExist($clientId)
        ) {
            throw new \Exception('Client not found!', 400);
        }

        $creatorId = $this->creatorId;
        if (!Creator::isExist($creatorId)) {
            throw new \Exception('Creator not found!', 400);
        }

        $expertId = $this->expertId;
        if (!Creator::isExist($expertId)) {
            throw new \Exception('Expert not found!', 400);
        }
    }

    //можно $templateData сделать свойством класса
    private function getTemplateData(): array
    {
        $differences = $this->getDifferences();
        $templateData = [
            'COMPLAINT_ID'       => (int) $data['complaintId'],
            'COMPLAINT_NUMBER'   => (string) $data['complaintNumber'],
            'CREATOR_ID'         => (int) $data['creatorId'],
            'CREATOR_NAME'       => $cr->getFullName(),
            'EXPERT_ID'          => (int) $data['expertId'],
            'EXPERT_NAME'        => $et->getFullName(),
            'CLIENT_ID'          => (int) $data['clientId'],
            'CLIENT_NAME'        => $cFullName,
            'CONSUMPTION_ID'     => (int) $data['consumptionId'],
            'CONSUMPTION_NUMBER' => (string) $data['consumptionNumber'],
            'AGREEMENT_NUMBER'   => (string) $data['agreementNumber'],
            'DATE'               => (string) $data['date'],
            'DIFFERENCES'        => $differences,
        ];
        return $templateData;
    }

    private function getDifferences(): string
    {
        $differences = "";
        if ($this->notificationType === self::TYPE_NEW) {
            $differences = __('NewPositionAdded', null, $this->resellerId);
        } elseif ($this->notificationType === self::TYPE_CHANGE && !empty($data['differences'])) {
            $differences = __('PositionStatusHasChanged', [
                'FROM' => Status::from((int) $data['differences']['from']),
                'TO'   => Status::from((int) $data['differences']['to']),
            ], $this->resellerId);
        }
        return $differences;
    }


}