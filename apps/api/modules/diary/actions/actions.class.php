<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * diary api actions.
 *
 * @package    OpenPNE
 * @subpackage action
 * @author     Shunsuke Watanabe <watanabe@craftgear.net>
 */
class diaryActions extends opJsonApiActions
{
  public function preExecute()
  {
    parent::preExecute();
    //myUser.class.php内でApiキーのチェックが行われているので
    //preExecuteでユーザ情報を取得してチェックを走らせる
    $this->member = $this->getUser()->getMember();
  }

  public function executePost(sfWebRequest $request)
  {
    $this->forward400If('' === (string)$request['title'], 'title parameter is not specified.');
    $this->forward400If('' === (string)$request['body'], 'body parameter is not specified.');
    $this->forward400If(!isset($request['public_flag']) || '' === (string)$request['public_flag'], 'public flag is not specified');

    if(isset($request['id']) && '' !== $request['id'])
    {
      $diary = Doctrine::getTable('Diary')->findOneById($request['id']);
    }
    else
    {
      $diary = new Diary();
    }
    $diary->setMemberId($this->member->getId());
    $diary->setTitle($request['title']);
    $diary->setBody($request['body']);
    $diary->setPublicFlag($request['public_flag']);
    $diary->save();

    $this->diary = $diary;

    //TODO アクティビティに日記の投稿を表示するようにする
  }

  public function executeDelete(sfWebRequest $request)
  {
    $this->forward400If(!isset($request['id']) || '' === (string)$request['id'], 'a diary id is not specified');

    $diary = Doctrine::getTable('Diary')->findOneById($request['id']);
    $this->forward400If(false == $diary->isAuthor($this->member->getId()), 'this diary entry is not yours');

    $isDeleted = $diary->delete();

    if ($isDeleted)
    {
      $this->id = $request['id'];
      //TODO アクティビティから日記の投稿を削除する
    }
    else
    {
      $this->forward400('failed to delete the entry. errorStack:'.$diary->getErrorStackAsString());
    }

  }

  public function executeList(sfWebRequest $request)
  {
    $page = isset($request['page']) ? $request['page'] : 1;
    $limit = sfConfig::get('op_json_api_limit', 15);
    $query = Doctrine::getTable('Diary')->createQuery('c')
      ->orderBy('created_at desc')
      ->offset(($page - 1) * $limit)
      ->limit($limit);
    if ($request['id'])
    {
      $query->addWhere('member_id = ?', $request['id']);
    }

    $this->diaries = $query->execute();
    $total = $query->count();
    $this->next = false;
    if ($total > $page * $limit)
    {
      $this->next = $page + 1;
    }
  }

  public function executeShow(sfWebRequest $request)
  {
    $this->forward400If(!isset($request['id']) || '' === (string)$request['id'], 'id is not specified');

    $this->memberId = $this->getUser()->getMemberId();
    $this->diary = Doctrine::getTable('Diary')->findOneById($request['id']);
  }

}
