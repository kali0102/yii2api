<?php
/**
 * Created by PhpStorm.
 * User: xuguozhi
 * Date: 2018/1/16
 * Time: 10:56
 */

namespace account\form\rbac;

use account\form\Model;
use account\models\rbac\AuthAssignment;
use account\models\rbac\AuthItem;
use account\models\rbac\AuthItemChild;
use account\models\rbac\AuthRule;
use Yii;
use yii\web\HttpException;

/**
 * 表单模型
 * Class AuthItemDeleteRolePermissionsForm
 * @package account\form\rbac
 */
class AuthItemDeleteRolePermissionsForm extends Model
{
    public $name;
    public $role;

    /**
     * 验证规则
     * @return array
     */
    public function rules()
    {
        return [
            [['name', 'role'], 'safe', 'on' => 'delete-role-permissions'],
            [['name', 'role'], 'required', 'on' => 'delete-role-permissions'],
            [['name', 'role'], 'trim', 'on' => 'delete-role-permissions'],
            [['name', 'role'], 'string', 'on' => 'delete-role-permissions'],
            [['name'], 'exist', 'targetClass' => AuthItem::className(), 'targetAttribute' => ['name' => 'name'], 'filter' => ['type' => 2], 'on' => 'delete-role-permissions'],
            [['role'], 'exist', 'targetClass' => AuthItem::className(), 'targetAttribute' => ['role' => 'name'], 'filter' => ['type' => 1], 'on' => 'delete-role-permissions'],
            [['name'], 'validateNameAndRole', 'when' => function($model){
                return !$model->hasErrors();
            } , 'on' => 'delete-role-permissions']
        ];
    }

    /**
     * 场景
     * @return array
     */
    public function scenarios()
    {
        return [
            'delete-role-permissions' => [
                'name',
                'role'
            ]
        ];
    }

    /**
     * 验证权限、角色关系是否合法
     * @param $attribute
     * @param $params
     */
    public function validateNameAndRole($attribute, $params)
    {
        // 是否已存在该数据
        $authItemChild = AuthItemChild::exists($this->role, $this->name);
        if (!$authItemChild) {
            $this->addError($attribute, Yii::t('app/error', 'the data not exist'));
            return;
        }
    }

    /**
     * 属性标签
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'name' => Yii::t('app\attribute', 'name'),
            'role' => Yii::t('app\attribute', 'role'),
        ];
    }

    /***************************** 表单操作 *********************************/

    /***************************** 获取数据 *********************************/

    /**
     * 为角色删除权限
     * @param $param
     * @throws HttpException
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     */
    public static function deleteRolePermissions($param)
    {
        // 表单模型实例化
        $authItemDeleteRolePermissionsForm = new AuthItemDeleteRolePermissionsForm();
        // 场景定义
        $authItemDeleteRolePermissionsForm->setScenario('delete-role-permissions');
        // 验证数据是否合法
        if ($authItemDeleteRolePermissionsForm->load([$authItemDeleteRolePermissionsForm->formName() => $param]) && $authItemDeleteRolePermissionsForm->validate()) {
            // 数据合法
            // 过滤后的合法数据
            $attributes = $authItemDeleteRolePermissionsForm->getAttributes();
            // 顺便清除缓存依赖对应的子数据
            (new AuthItem())->tagDependencyInvalidate();
            (new AuthItemChild())->tagDependencyInvalidate();
            (new AuthAssignment())->tagDependencyInvalidate();
            (new AuthRule())->tagDependencyInvalidate();

            $auth = Yii::$app->getAuthManager();
            $permission = $auth->createRole($attributes['name']);
            $role = $auth->createRole($attributes['role']);
            if ($auth->removeChild($role, $permission)) {
                throw new HttpException(200, Yii::t('app/success', 'data delete successfully'));
            } else {
                throw new HttpException(500, Yii::t('app/error', 'server internal error'));
            }
        } else {
            // 数据不合法
            throw new HttpException(422, $authItemDeleteRolePermissionsForm->getFirstError());
        }
    }

}