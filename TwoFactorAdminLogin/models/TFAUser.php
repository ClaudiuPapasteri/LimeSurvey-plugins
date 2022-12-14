<?php

/**
 * Abstracted user model for TFA admin view.
 * Incorporating an alternative seach method.
 *
 * @inheritDoc
 */

class TFAUser extends User
{
    public function attributeLabels()
    {
        return [
            'authType' => 'Type of 2-Factor-Authentication used',
            'secretKey' => 'Authentication base value',
            'uid' => gT('User ID'),
            'firstLogin' => 'Logged in with 2FA',
            'forceNewFirstLogin' => 'Force to set 2FA'
        ];
    }

    /**
    * @inheritDoc
    */
    public static function model($className = __CLASS__)
    {
        /** @var self $model */
        $model = parent::model($className);
        return $model;
    }

    /** @inheritDoc */
    public function relations()
    {
        return array(
            'userkeys'  => array(self::HAS_ONE, 'TFAUserKey', array('uid')),
        );
    }

    /** @inheritDoc */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = ['users_name', 'safe', 'on'=>'search'];
        $rules[] = ['email', 'safe', 'on'=>'search'];
        $rules[] = ['full_name', 'safe', 'on'=>'search'];
        return $rules;
    }

    /**
     * Returns the action columsn buttons
     *
     * @return string
     */
    public function getButtons()
    {
        if(!$this->hasAuthSet) {
            return '';
        }

        return ''
        .'<button '
            .'class="btn btn-icon btn-danger btn-sm TFA--management--action-deleteToken" '
            .'title="'.gT("Delete 2FA-token").'" '
            .'data-toggle="tooltip" '
            .'data-confirmtext="'.gT('Are you sure to delete this 2FA-token?').'" '
            .'data-buttons="{confirm_cancel: \''.gT('No, cancel').'\', confirm_ok: \''.gT('Yes, i am sure').'\'}" '
            .'data-href="'.Yii::app()->createUrl("plugins/direct/plugin/TwoFactorAdminLogin/function/directCallDeleteKey").'" '
            .'data-uid="'.$this->uid.'" '
            .'data-errortext="'.gT('An error has happened, and the token could not be deleted.').'" '
        .'>'
            .'<i class="fa fa-trash"></i>'
        .'</button>&nbsp;'
        .'';
    }

    /**
     * Return the related descriptive UserKey value for this auth type
     *
     * @return string
     */
    public function getAuthTypeDescription()
    {
        if ($this->hasAuthSet) {
            return TFAUserKey::$authTypeOptions[$this->userkeys->authType];
        }
        return '';
    }

    /**
     * Returns a check value of if the user has a bound 2FA secret key
     *
     * @return bool
     */
    public function getHasAuthSet()
    {
        return $this->userkeys != null;
    }

    /**
     * @inheritDoc
     * @return array
     */
    public function getColums()
    {
        // TODO should be static
        $cols = array(
             array(
                 "name" => 'buttons',
                 "type" => 'raw',
                 "filter" => false,
                 "header" => gT("Action")
             ),
             array(
                 "name" => 'users_name',
                 "header" => gT("Username"),
             ),
             array(
                 "name" => 'full_name',
                 "header" => gT("Full name"),
             ),
             array(
                 "name" => 'email',
                 "header" => gT("Email"),
             ),
             array(
                 "name" => 'userkeys.authType',
                 "header" => gT("2FA-Method"),
                 "filter" => TbHtml::dropDownList('userkeys_authType', Yii::app()->request->getParam('userkeys_authType'), array_merge([''=>''], TFAUserKey::$authTypeOptions)),
             ),
             array(
                 "name" => 'hasAuthSet',
                 "header" => gT("2FA enabled"),
                 "filter" => TbHtml::dropDownList('userkeys_secretKey', Yii::app()->request->getParam('userkeys_secretKey'), [
                     '' => '',
                     '0' => gT('Has 2FA disabled'),
                     '1' => gT('Has 2FA enabled'),
                    ]),
             ),
         );
        return $cols;
    }
 
    /** @inheritDoc */
    public function search()
    {
        // @todo Please modify the following code to remove attributes that should not be searched.
        $pageSize = Yii::app()->user->getState('pageSize', Yii::app()->params['defaultPageSize']);

        $criteria = new CDbCriteria;
        $criteria->with = 'userkeys';
        $criteria->compare('users_name', $this->users_name);
        $criteria->compare('full_name', $this->full_name);
        $criteria->compare('email', $this->email);
        $criteria->compare('userkeys', Yii::app()->request->getParam('userkeys_authType'));
        $paramHasAuthSet = Yii::app()->request->getParam('userkeys_secretKey', '');
        if ($paramHasAuthSet === '1') {
            $criteria->addCondition('secretKey IS NOT NULL');
        }
        if ($paramHasAuthSet === '0') {
            $criteria->addCondition('secretKey IS NULL');
        }
         
        $oDataProvider =  new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
            'pagination' => [
                'pageSize' => $pageSize
            ],
            'sort' => [
                 'attributes' => [
                    'users_name' => [
                        'asc' => 'users_name',
                        'desc' => 'users_name desc',
                    ],
                    'full_name' => [
                        'asc' => 'full_name asc',
                        'desc' => 'full_name desc',
                    ],
                    'email' => [
                        'asc' => 'email asc',
                        'desc' => 'email desc',
                    ],
                    'userkeys.authType' => [
                        'asc' => 'userkeys.authType asc',
                        'desc' => 'userkeys.authType desc',
                    ],
                    'hasAuthSet' => [
                        'asc' => 'userkeys.secretKey asc',
                        'desc' => 'userkeys.secretKey desc',
                    ],
                 ]
            ],
         ));
        return $oDataProvider;
    }
}
