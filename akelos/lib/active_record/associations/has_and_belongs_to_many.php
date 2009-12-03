<?php


/**
* Associates two classes via an intermediate join table.  Unless the join table is explicitly specified as
* an option, it is guessed using the lexical order of the class names. So a join between Developer and Project
* will give the default join table name of "developers_projects" because "D" outranks "P".
*
* Adds the following methods for retrieval and query.
* 'collection' is replaced with the associton identification passed as the first argument, so
* <tt>var $has_and_belongs_to_many = 'categories'</tt> would make available on the its parent an array of
*  objects on $this->categories and a collection handling interface instance on $this->category (singular form)
* * <tt>collection->load($force_reload = false)</tt> - returns an array of all the associated objects.
*   An empty array is returned if none is found.
* * <tt>collection->add($object, ...)</tt> - adds one or more objects to the collection by creating associations in the join table
*   (collection->push and $collection->concat are aliases to this method).
* * <tt>collection->pushWithAttributes($object, $join_attributes)</tt> - adds one to the collection by creating an association in the join table that
*   also holds the attributes from <tt>join_attributes</tt> (should be an array with the column names as keys). This can be used to have additional
*   attributes on the join, which will be injected into the associated objects when they are retrieved through the collection.
*   (collection->concatWithAttributes() is an alias to this method).
* * <tt>collection->delete($object, ...)</tt> - removes one or more objects from the collection by removing their associations from the join table.
*   This does not destroy the objects.
* * <tt>collection->set($objects)</tt> - replaces the collections content by deleting and adding objects as appropriate.
* * <tt>collection->setByIds($ids)</tt> - replace the collection by the objects identified by the primary keys in $ids
* * <tt>collection->clear()</tt> - removes every object from the collection. This does not destroy the objects.
* * <tt>collection->isEmpty()</tt> - returns true if there are no associated objects.
* * <tt>collection->size()</tt> - returns the number of associated objects. (collection->count() is an alias to this method)
* * <tt>collection->find($id)</tt> - finds an associated object responding to the +id+ and that
*   meets the condition that it has to be associated with this object.
*
* Example: A Developer class declares <tt>var $has_and_belongs_to_many = 'projects'</tt>, which will add:
* * <tt>Developer->projects</tt> (The collection)
* * <tt>Developer->project</tt> (The association manager)
* * <tt>Developer->project->add()<<</tt>
* * <tt>Developer->project->pushWithAttributes()</tt>
* * <tt>Developer->project->delete()</tt>
* * <tt>Developer->project->set()</tt>
* * <tt>Developer->project->setByIds()</tt>
* * <tt>Developer->project->clear()</tt>
* * <tt>Developer->projects->isEmpty()</tt>
* * <tt>Developer->projects->size()</tt>
* * <tt>Developer->projects->find($id)</tt>
*
* The declaration may include an options hash to specialize the behavior of the association.
*
* Options are:
* * <tt>class_name</tt> - specify the class name of the association. Use it only if that name can't be inferred
*   from the association name. So <tt>var $has_and_belongs_to_many = 'projects'</tt> will by default be linked to the
*   Project class, but if the real class name is SuperProject, you'll have to specify it with this option.
* * <tt>table_name</tt> - Name for the associated object database table. As this association will not instantiate the associated model
* it nees to know the name of the table we are going to join. This is infered form previous class_name
* * <tt>join_table</tt> - specify the name of the join table if the default based on lexical order isn't what you want.
*   WARNING: If you're overwriting the table name of either class, the table_name method MUST be declared underneath any
*   $has_and_belongs_to_many declaration in order to work.
* * <tt>join_class_name</tt> - specify the class name of the association join table. If the class does not exist, a new class
* will be created on runtime to load the results into the collection. The class name will be infered from the join_table name
* * <tt>foreign_key</tt> - specify the foreign key used for the association. By default this is guessed to be the name
*   of this class in lower-case and "_id" suffixed. So a Person class that makes a $has_and_belongs_to_many association
*   will use "person_id" as the default foreign_key.
* * <tt>association_foreign_key</tt> - specify the association foreign key used for the association. By default this is
*   guessed to be the name of the associated class in lower-case and "_id" suffixed. So if the associated class is Project,
*   the has_and_belongs_to_many association will use "project_id" as the default association foreign_key.
* * <tt>conditions</tt>  - specify the conditions that the associated object must meet in order to be included as a "WHERE"
*   sql fragment, such as "authorized = 1".
* * <tt>order</tt> - specify the order in which the associated objects are returned as a "ORDER BY" sql fragment, such as "last_name, first_name DESC"
* * <tt>finder_sql</tt> - overwrite the default generated SQL used to fetch the association with a manual one
* * <tt>delete_sql</tt> - overwrite the default generated SQL used to remove links between the associated
*   classes with a manual one
* * <tt>insert_sql</tt> - overwrite the default generated SQL used to add links between the associated classes
*   with a manual one
* * <tt>include</tt>  - specify second-order associations that should be eager loaded when the collection is loaded.
* * <tt>group</tt>: An attribute name by which the result should be grouped. Uses the GROUP BY SQL-clause.
* * <tt>limit</tt>: An integer determining the limit on the number of rows that should be returned.
* * <tt>offset</tt>: An integer determining the offset from where the rows should be fetched. So at 5, it would skip the first 4 rows.
* * <tt>select</tt>: By default, this is * as in SELECT * FROM, but can be changed if you for example want to do a join, but not
*   include the joined columns.
* * <tt>unique</tt> - if set to true, duplicates will be omitted from the collection.
*
* Option examples:
*   $has_and_belongs_to_many = 'projects';
*   $has_and_belongs_to_many = array('projects'=> array('include' => array('milestones', 'manager')))
*   $has_and_belongs_to_many = array('nations' => array('class_name' => "Country"))
*   $has_and_belongs_to_many = array('categories' => array('join_table' => "prods_cats"))
*   $has_and_belongs_to_many = array('active_projects' => array('join_table' => 'developers_projects', 'delete_sql' =>
*   'DELETE FROM developers_projects WHERE active=1 AND developer_id = $id AND project_id = $record->id'
*/
class AkHasAndBelongsToMany extends AkAssociation
{
    /**
     * Join object place holder
     */
    public
    $JoinObject,
    $associated_ids = array(),
    $association_id;

    protected
    $_automatically_create_join_model_files = AK_HAS_AND_BELONGS_TO_MANY_CREATE_JOIN_MODEL_CLASSES;

    public function &addAssociated($association_id, $options = array())
    {
        $default_options = array(
        'class_name' => empty($options['class_name']) ? AkInflector::classify($association_id) : $options['class_name'],
        'table_name' => false,
        'join_table' => false,
        'join_class_name' => false,
        'foreign_key' => false,
        'association_foreign_key' => false,
        'conditions' => false,
        'order' => false,
        'join_class_extends' => AK_HAS_AND_BELONGS_TO_MANY_JOIN_CLASS_EXTENDS,
        'join_class_primary_key' => 'id', // Used for removing items from the collection
        'finder_sql' => false,
        'delete_sql' => false,
        'insert_sql' => false,
        'include' => false,
        'group' => false,
        'limit' => false,
        'offset' => false,
        'handler_name' => strtolower(AkInflector::underscore(AkInflector::singularize($association_id))),
        'select' => false,
        'instantiate' => false,
        'unique' => false
        /*
        'include_conditions_when_included' => true,
        'include_order_when_included' => true,
        'counter_sql' => false,
        */

        );

        $options = array_merge($default_options, $options);

        $owner_name = $this->Owner->getModelName();
        $owner_table = $this->Owner->getTableName();
        $associated_name = $options['class_name'];
        $associated_table_name = $options['table_name'] = (empty($options['table_name']) ? AkInflector::tableize($associated_name) : $options['table_name']);

        $join_tables = array($owner_table, $associated_table_name);
        sort($join_tables);

        $options['join_table'] = empty($options['join_table']) ? join('_', $join_tables) : $options['join_table'];
        $options['join_class_name'] = empty($options['join_class_name']) ? join(array_map(array('AkInflector','classify'),array_map(array('AkInflector','singularize'), $join_tables))) : $options['join_class_name'];
        $options['foreign_key'] = empty($options['foreign_key']) ? AkInflector::underscore($owner_name).'_id' : $options['foreign_key'];
        $options['association_foreign_key'] = empty($options['association_foreign_key']) ? AkInflector::underscore($associated_name).'_id' : $options['association_foreign_key'];

        $Collection = $this->_setCollectionHandler($association_id, $options['handler_name']);
        $Collection->setOptions($association_id, $options);

        $this->addModel($association_id,  $Collection);

        if($options['instantiate']){
            $associated = $Collection->load();
        }

        $this->setAssociatedId($association_id, $options['handler_name']);
        $Collection->association_id = $association_id;

        $Collection->_loadJoinObject();

        return $Collection;
    }

    public function getType()
    {
        return 'hasAndBelongsToMany';
    }

    public function &_setCollectionHandler($association_id, $handler_name)
    {
        $false = false;
        if(isset($this->Owner->$association_id)){
            if(!is_array($this->Owner->$association_id)){
                trigger_error(Ak::t('%model_name::%association_id is not a collection array on current %association_id hasAndBelongsToMany association',array('%model_name'=>$this->Owner->getModelName(), '%association_id'=>$association_id)), E_USER_NOTICE);
            }
            $associated = $this->Owner->$association_id;
        }else{
            $associated = array();
            $this->Owner->$association_id = $associated;
        }

        if(isset($this->Owner->$handler_name)){
            trigger_error(Ak::t('Could not load %association_id on %model_name because "%model_name->%handler_name" attribute '.
            'is already defined and can\'t be used as an association placeholder',
            array('%model_name'=>$this->Owner->getModelName(),'%association_id'=>$association_id, '%handler_name'=>$handler_name)),
            E_USER_ERROR);
            return $false;
        }else{
            $this->Owner->$handler_name = new AkHasAndBelongsToMany($this->Owner);
        }
        return $this->Owner->$handler_name;
    }


    // We are going to load or create the join class
    public function _loadJoinObject()
    {
        if($this->_isJoinObjectLoaded()){
            return true;
        }
        $options = $this->getOptions($this->association_id);

        if (class_exists($options['join_class_name']) || $this->_loadJoinClass($options['join_class_name']) || $this->_createJoinClass()) {
            $this->JoinObject = new $options['join_class_name']();
            if($this->_tableExists($options['join_table']) || $this->_createJoinTable()){
                $this->JoinObject->setPrimaryKey($options['foreign_key']);
                return true;

            } else {
                trigger_error(Ak::t('Could not find join table %table_name for hasAndBelongsToMany association %id',array('%table_name'=>$options['join_table'],'id'=>$this->association_id)),E_USER_ERROR);
            }
        } else {
            trigger_error(Ak::t('Could not find join model %model_name for hasAndBelongsToMany association %id',array('%table_name'=>$options['join_class_name'],'id'=>$this->association_id)),E_USER_ERROR); return false;
        }
        return false;
    }

    public function _isJoinObjectLoaded()
    {
        $options = $this->getOptions($this->association_id);
        return !empty($this->JoinObject) && (strtolower($options['join_class_name']) == strtolower(get_class($this->JoinObject)));
    }

    public function _loadJoinClass($class_name)
    {
        $model_file = AkInflector::toModelFilename($class_name);
        return file_exists($model_file) && require_once($model_file);
    }

    public function _createJoinClass()
    {
        $options = $this->getOptions($this->association_id);

        $class_file_code = "<?php \n\n//This code was generated automatically by the active record hasAndBelongsToMany Method\n\n";
        $class_code =
        "class {$options['join_class_name']} extends {$options['join_class_extends']} {
    public \$_avoidTableNameValidation = true;
    public function {$options['join_class_name']}()
    {
        \$this->setModelName(\"{$options['join_class_name']}\");
        \$attributes = (array)func_get_args();
        \$this->setTableName('{$options['join_table']}', true, true);
        \$this->init(\$attributes);
    }
}";
        $class_file_code .= $class_code. "\n\n?>";
        $join_file = AkInflector::toModelFilename($options['join_class_name']);
        if($this->_automatically_create_join_model_files && !file_exists($join_file) && @Ak::file_put_contents($join_file, $class_file_code)){
            require_once($join_file);
        }else{
            eval($class_code);
        }
        return class_exists($options['join_class_name']);
    }

    public function _tableExists($table_name)
    {
        return $this->JoinObject->setTableName($table_name, true, true);
    }

    public function _createJoinTable()
    {
        $options = $this->getOptions($this->association_id);
        $Installer = new AkInstaller();
        $Installer->createTable($options['join_table'],"id,{$options['foreign_key']},{$options['association_foreign_key']}",array('timestamp'=>false));
        return $this->JoinObject->setTableName($options['join_table'],false);
    }

    public function &load($force_reload = false)
    {
        $options = $this->getOptions($this->association_id);
        if($force_reload || empty($this->Owner->{$options['handler_name']}->_loaded)){
            if(!$this->Owner->isNewRecord()){
                $this->constructSql();
                $options = $this->getOptions($this->association_id);
                $Associated = $this->getAssociatedModelInstance();
                if($FoundAssociates = $Associated->findBySql($options['finder_sql'])){
                    array_map(array( $this,'_setAssociatedMemberId'),$FoundAssociates);
                    $this->Owner->{$this->association_id} = $FoundAssociates;
                }
            }
            if(empty($this->Owner->{$this->association_id})){
                $this->Owner->{$this->association_id} = array();
            }

            $this->Owner->{$options['handler_name']}->_loaded = true;
        }
        return $this->Owner->{$this->association_id};
    }


    /**
     * add($object), add(array($object, $object2)) - adds one or more objects to the collection by setting
     * their foreign keys to the collection?s primary key. Items are saved automatically when parent has been saved.
     */
    public function add(&$Associated)
    {
        if(is_array($Associated)){
            $external_key = '__associated_to_model_'.$this->Owner->getModelName().'_as_'.$this->association_id;
            $succes = true;
            $succes = $this->Owner->notifyObservers('beforeAdd') ? $succes : false;
            $options = $this->getOptions($this->association_id);
            foreach (array_keys($Associated) as $k){
                if(empty($Associated[$k]->$external_key) && !$this->_hasAssociatedMember($Associated[$k])){
                    if(!empty($options['before_add']) && method_exists($this->Owner, $options['before_add']) && $this->Owner->{$options['before_add']}($Associated[$k]) === false ){
                        $succes = false;
                    }else{
                        if(method_exists($Associated[$k], 'isNewRecord')){
                            $Associated[$k]->$external_key = $Associated[$k]->isNewRecord();
                            $this->Owner->{$this->association_id}[] = $Associated[$k];
                            $this->_setAssociatedMemberId($Associated[$k]);
                            if($this->_relateAssociatedWithOwner($Associated[$k])){
                                if($succes && !empty($options['after_add']) && method_exists($this->Owner, $options['after_add']) && $this->Owner->{$options['after_add']}($Associated[$k]) === false ){
                                    $succes = false;
                                }
                            }else{
                                $succes = false;
                            }
                        }else{
                            trigger_error(Ak::t('Could not handle habtm association for %model_name::%association_name', array('%model_name' => $this->Owner->getModelName(), '%association_name' => $this->association_id)), E_USER_NOTICE);
                            $succes = false;
                        }
                    }
                }
            }
            $succes = $this->Owner->notifyObservers('afterAdd') ? $succes : false;
            return $succes;
        }else{
            $associates = array();
            $associates[] = $Associated;
            return $this->add($associates);
        }
    }


    public function push(&$record)
    {
        return $this->add($record);
    }

    public function concat(&$record)
    {
        return $this->add($record);
    }

    /**
    * Remove all records from this association
    */
    public function deleteAll()
    {
        $this->load();
        return $this->delete($this->Owner->{$this->association_id});
    }

    public function reset()
    {
        $options = $this->getOptions($this->association_id);
        $this->Owner->{$options['handler_name']}->_loaded = false;
    }

    public function set(&$objects)
    {
        $this->deleteAll($objects);
        $this->add($objects);
    }

    public function setIds()
    {
        $ids = func_get_args();
        $ids = is_array($ids[0]) ? $ids[0] : $ids;
        $AssociatedModel = $this->getAssociatedModelInstance();
        if(!empty($ids)){
            $NewAssociates = $AssociatedModel->find($ids);
            $this->set($NewAssociates);
        }
    }

    public function setByIds()
    {
        $ids = func_get_args();
        call_user_func_array(array($this,'setIds'), $ids);
    }

    public function addId($id)
    {
        $AssociatedModel = $this->getAssociatedModelInstance();
        if($NewAssociated = $AssociatedModel->find($id)){
            return $this->add($NewAssociated);
        }
        return false;
    }


    public function delete(&$Associated)
    {
        $success = true;
        if(!is_array($Associated)){
            $associated_elements = array();
            $associated_elements[] = $Associated;
            return $this->delete($associated_elements);
        }else{
            $options = $this->getOptions($this->association_id);

            $ids_to_nullify = array();
            $ids_to_delete = array();
            $items_to_remove_from_collection = array();

            $this->JoinObject->setPrimaryKey($options['join_class_primary_key']);

            foreach (array_keys($Associated) as $k){
                $id = $Associated[$k]->getId();
                if($JoinObjectsToDelete = $this->JoinObject->findAllBy($options['foreign_key'].' AND '.$options['association_foreign_key'], $this->Owner->getId(), $id)){
                    foreach (array_keys($JoinObjectsToDelete) as $k) {
                        if($JoinObjectsToDelete[$k]->destroy()){
                            $this->_deleted_join_object_values[$this->Owner->getId()][$id]=$JoinObjectsToDelete[$k]->getAttributes();
                            $items_to_remove_from_collection[] = $id;
                        }else{
                            $success = false;
                        }
                    }
                }
            }

            $this->JoinObject->setPrimaryKey($options['foreign_key']);

            $success ? $this->removeFromCollection($items_to_remove_from_collection) : null;
        }

        return $success;
    }



    /**
    * Remove records from the collection. Use delete() in order to trigger database dependencies
    */
    public function removeFromCollection(&$records)
    {
        if(!is_array($records)){
            $records_array = array();
            $records_array[] = $records;
            $this->delete($records_array);
        }else{
            $this->Owner->notifyObservers('beforeRemove');
            $options = $this->getOptions($this->association_id);
            foreach (array_keys($records) as $k){
                if(!empty($options['before_remove']) && method_exists($this->Owner, $options['before_remove']) && $this->Owner->{$options['before_remove']}($records[$k]) === false ){
                    continue;
                }

                if($records[$k] instanceof AkActiveRecord){
                    $record_id = $records[$k]->getId();
                }else{
                    $record_id = $records[$k];
                }
                foreach (array_keys($this->Owner->{$this->association_id}) as $kk){
                    if(
                    (
                    !empty($this->Owner->{$this->association_id}[$kk]->__hasAndBelongsToManyMemberId) &&
                    !empty($records[$k]->__hasAndBelongsToManyMemberId) &&
                    $records[$k]->__hasAndBelongsToManyMemberId == $this->Owner->{$this->association_id}[$kk]->__hasAndBelongsToManyMemberId
                    ) || (
                    $this->Owner->{$this->association_id}[$kk] instanceof AkActiveRecord &&
                    $record_id == $this->Owner->{$this->association_id}[$kk]->getId()
                    )
                    ){
                        unset($this->Owner->{$this->association_id}[$kk]);
                    }
                }
                unset($this->associated_ids[$record_id]);

                if(!empty($options['after_remove']) && method_exists($this->Owner, $options['after_remove'])){
                    $this->Owner->{$options['after_remove']}($records[$k]);
                }
            }
            $this->Owner->notifyObservers('afterRemove');
        }
    }




    public function _setAssociatedMemberId(&$Member)
    {
        if(empty($Member->__hasAndBelongsToManyMemberId)) {
            $Member->__hasAndBelongsToManyMemberId = Ak::randomString();
        }
        $object_id = $Member->getId();
        if(!empty($object_id)){
            $this->associated_ids[$object_id] = $Member->__hasAndBelongsToManyMemberId;
        }
    }

    public function _unsetAssociatedMemberId(&$Member)
    {
        $id = $this->_getAssociatedMemberId($Member);
        unset($this->associated_ids[$id]);
        unset($Member->__hasAndBelongsToManyMemberId);
    }

    public function _getAssociatedMemberId(&$Member)
    {
        if(!empty($Member->__hasAndBelongsToManyMemberId)) {
            return array_search($Member->__hasAndBelongsToManyMemberId, $this->associated_ids);
        }
        return false;
    }

    public function _hasAssociatedMember(&$Member)
    {
        $options = $this->getOptions($this->association_id);
        if($options['unique'] && !$Member->isNewRecord() && isset($this->associated_ids[$Member->getId()])){
            return true;
        }
        $id = $this->_getAssociatedMemberId($Member);
        return !empty($id);
    }


    public function _relateAssociatedWithOwner(&$Associated)
    {

        if(!$this->Owner->isNewRecord()){
            $success = true;
            $options = $this->getOptions($this->association_id);
            if(strtolower($options['join_class_name']) != strtolower(get_class($this->JoinObject))){
                return false;
            }
            if($Associated->isNewRecord() ? $Associated->save() : true){
                if(!$this->_getAssociatedMemberId($Associated)){
                    $this->_setAssociatedMemberId($Associated);
                }

                $foreign_key = $this->Owner->getId();
                $association_foreign_key = $Associated->getId();
                if($foreign_key != $this->JoinObject->get($options['foreign_key']) ||
                $association_foreign_key != $this->JoinObject->get($options['association_foreign_key'])){
                    $addValues = array();
                    if (@isset($this->_deleted_join_object_values[$foreign_key][$association_foreign_key])) {
                        $oldAttributes = $this->_deleted_join_object_values[$foreign_key][$association_foreign_key];
                        $content_columns = array_keys($this->JoinObject->getContentColumns());

                        foreach($content_columns as $c) {

                            if (isset($oldAttributes[$c])) {
                                $addValues[$c] = $oldAttributes[$c];
                            }
                        }

                        $pkName=$options['join_class_primary_key'];
                        if(isset($oldAttributes[$pkName])) {
                            $addValues[$pkName] = $oldAttributes[$pkName];
                        }
                    }
                    $attributes = array($options['foreign_key']=> $foreign_key, $options['association_foreign_key']=> $association_foreign_key);
                    $attributes = array_merge($attributes,$addValues);
                    $this->JoinObject = $this->JoinObject->create($attributes);
                    $success = !$this->JoinObject->isNewRecord();
                }
            }
            if($success){
                //$Associated->hasAndBelongsToMany->__joined = true;
            }
            return $success;
        }
        return false;
    }

    public function &_build($association_id, &$AssociatedObject, $reference_associated = true)
    {
        if($reference_associated){
            $this->Owner->$association_id = $AssociatedObject;
        }else{
            $this->Owner->$association_id = $AssociatedObject;
        }
        $this->Owner->$association_id->_AssociationHandler = $this;
        $this->Owner->$association_id->_associatedAs = $this->getType();
        $this->Owner->$association_id->_associationId = $association_id;
        $this->Owner->_associations[$association_id] = $this->Owner->$association_id;
        return $this->Owner->$association_id;
    }

    public function addTableAliasesToAssociatedSql($table_alias, $sql)
    {
        return preg_replace($this->Owner->getColumnsWithRegexBoundaries(),'\1'.$table_alias.'.\2',' '.$sql.' ');
    }

    public function constructSql()
    {
        $options = $this->getOptions($this->association_id);
        if(empty($options['finder_sql'])){
            $owner_id=$this->Owner->quotedId();
            if (empty($owner_id)) $owner_id=-1;
            $is_sqlite = $this->Owner->_db->type() == 'sqlite';
            $options['finder_sql'] = "SELECT {$options['table_name']}.* FROM {$options['table_name']} ".
            $this->associationJoin2().
            "WHERE _{$options['join_class_name']}.{$options['foreign_key']} ".
            ($is_sqlite ? ' LIKE ' : ' = ').' '.$owner_id; // (HACK FOR SQLITE) Otherwise returns wrong data
            $options['finder_sql'] .= !empty($options['conditions']) ? ' AND '.$options['conditions'].' ' : '';

        } else {
            $owner_id=$this->Owner->quotedId();
            if (empty($owner_id)) $owner_id=-1;
            $options['finder_sql'] = str_replace(array(':foreign_key_value'),array($owner_id), $options['finder_sql']);

        }
        $options['finder_sql'].=!empty($options['group'])?' GROUP BY '.$options['group']:'';
        $options['finder_sql'].=!empty($options['order'])?' ORDER BY '.$options['order']:'';
        if(empty($options['counter_sql'])){
            $options['counter_sql'] = substr_replace($options['finder_sql'],'SELECT COUNT(*)',0,strpos($options['finder_sql'],'*')+1);
        } else {
            $owner_id=$this->Owner->quotedId();
            if (empty($owner_id)) $owner_id=-1;
            $options['counter_sql'] = str_replace(array(':foreign_key_value'),array($owner_id), $options['counter_sql']);

        }
        $this->setOptions($this->association_id, $options);
    }

    public function associationJoin()
    {
        $Associated = $this->getAssociatedModelInstance();
        $options = $this->getOptions($this->association_id);

        return "LEFT OUTER JOIN {$options['join_table']} ON ".
        "{$options['join_table']}.{$options['association_foreign_key']} = {$options['table_name']}.".$Associated->getPrimaryKey()." ".
        "LEFT OUTER JOIN ".$this->Owner->getTableName()." ON ".
        "{$options['join_table']}.{$options['foreign_key']} = ".$this->Owner->getTableName().".".$this->Owner->getPrimaryKey()." ";
    }

    public function associationJoin2()
    {
        $Associated = $this->getAssociatedModelInstance();
        $options = $this->getOptions($this->association_id);

        return "LEFT OUTER JOIN {$options['join_table']} AS _{$options['join_class_name']} ON ".
        "_{$options['join_class_name']}.{$options['association_foreign_key']} = {$options['table_name']}.".$Associated->getPrimaryKey()." ".
        "LEFT OUTER JOIN ".$this->Owner->getTableName()." AS _".$this->Owner->getModelName()." ON ".
        "_{$options['join_class_name']}.{$options['foreign_key']} = _".$this->Owner->getModelName().".".$this->Owner->getPrimaryKey()." ";
    }

    public function count()
    {
        $count = 0;
        $options = $this->getOptions($this->association_id);
        if(empty($this->Owner->{$options['handler_name']}->_loaded) && !$this->Owner->isNewRecord()){
            $this->constructSql();
            $options = $this->getOptions($this->association_id);
            $Associated = $this->getAssociatedModelInstance();

            if($this->_hasCachedCounter()){
                $count = $Associated->getAttribute($this->_getCachedCounterAttributeName());
            }elseif(!empty($options['counter_sql'])){
                $count = $Associated->countBySql($options['counter_sql']);
            }else{
                $count = (strtoupper(substr($options['finder_sql'],0,6)) != 'SELECT') ?
                $Associated->count($options['foreign_key'].'='.$this->Owner->quotedId()) :
                $Associated->countBySql($options['finder_sql']);
            }
        }else{
            $count = count($this->Owner->{$this->association_id});
        }

        if($count == 0){
            $this->Owner->{$this->association_id} = array();
            $this->Owner->{$options['handler_name']}->_loaded = true;
        }
        return $count;
    }


    public function &build($attributes = array(), $set_as_new_record = true)
    {
        $options = $this->getOptions($this->association_id);
        Ak::import($options['class_name']);
        $record = new $options['class_name']($attributes);
        $record->_newRecord = $set_as_new_record;
        $this->Owner->{$this->association_id}[] = $record;
        $this->_setAssociatedMemberId($record);
        $set_as_new_record ? $this->_relateAssociatedWithOwner($record) : null;
        return $record;
    }

    public function &create($attributes = array())
    {
        $record = $this->build($attributes);
        if(!$this->Owner->isNewRecord()){
            $record->save();
        }
        return $record;
    }


    public function getAssociatedFinderSqlOptions($association_id, $options = array())
    {
        $options = $this->getOptions($this->association_id);

        $Associated = $this->getAssociatedModelInstance();
        $table_name = $Associated->getTableName();
        $owner_id = $this->Owner->quotedId();

        $finder_options = array();

        foreach ($options as $option=>$value) {
            if(!empty($value)){
                $finder_options[$option] = trim($Associated->addTableAliasesToAssociatedSql('_'.$this->association_id, $value));
            }
        }

        $finder_options['joins'] = $this->constructSqlForInclusion();
        $finder_options['selection'] = '';

        foreach (array_keys($Associated->getColumns()) as $column_name){
            $finder_options['selection'] .= '_'.$this->association_id.'.'.$column_name.' AS _'.$this->association_id.'_'.$column_name.', ';
        }

        $finder_options['selection'] = trim($finder_options['selection'], ', ');

        /**
         * @todo Refactorize me. This is too confusing
         */
        $finder_options['conditions'] =
        // We add previous conditions
        (!empty($finder_options['conditions']) ?
        ''.$Associated->addTableAliasesToAssociatedSql('_'.$this->association_id, $options['conditions']).' ' : '');

        return $finder_options;
    }
    public function getAssociatedFinderSqlOptionsForInclusionChain($prefix, $parent_handler_name, $options = array(),$pluralize=false)
    {

        $association_options = $this->getOptions($this->association_id);
        $options = array_merge($association_options,$options);
        $handler_name = $options['handler_name'];
        $Associated = $this->getAssociatedModelInstance();
        $pk=$Associated->getPrimaryKey();
        $finder_options = array();

        foreach ($options as $option=>$value) {
            if(!empty($value)  && !is_bool($value)){
                if(is_string($value)) {
                    $finder_options[$option] = trim($Associated->addTableAliasesToAssociatedSql($parent_handler_name.'__'.$handler_name, $value));
                } else if(is_array($value)) {

                    foreach($value as $idx=>$v) {
                        $value[$idx]=trim($Associated->addTableAliasesToAssociatedSql($parent_handler_name.'__'.$handler_name, $v));
                    }
                    $finder_options[$option] = $value;
                }else {
                    $finder_options[$option] = $value;
                }
            }
        }

        $finder_options['joins'] = $this->constructSqlForInclusionChain($this->association_id,$handler_name,$parent_handler_name);
        $finder_options['selection'] = '';

        $selection_parenthesis = $this->_getColumnParenthesis();//
        foreach (array_keys($Associated->getColumns()) as $column_name){
            $finder_options['selection'] .= $parent_handler_name.'__'.$handler_name.'.'.$column_name.' AS '.$selection_parenthesis.$prefix.'['.$handler_name.']'.($pluralize?'[@'.$pk.']':'').'['.$column_name.']'.$selection_parenthesis.', ';
        }


        $join_class = $association_options['join_class_name'];
        Ak::import($join_class);
        $join_obj = new $join_class;
        $join_class_columns = array_keys($join_obj->getColumns());
        foreach ($join_class_columns as $column_name){
            $finder_options['selection'] .= $parent_handler_name.'__'.$handler_name.'__'.$join_class.'.'.$column_name.' AS '.$selection_parenthesis.$prefix.'['.$handler_name.']'.($pluralize?'[@'.$pk.']':'').'[_'.$join_class.']['.$column_name.']'.$selection_parenthesis.', ';
        }

        $finder_options['selection'] = trim($finder_options['selection'], ', ');

        /**
         * @todo Refactorize me. This is too confusing
         */
        /**$finder_options['conditions'] =
        // We add previous conditions
        (!empty($finder_options['conditions']) ?
        ''.$Associated->addTableAliasesToAssociatedSql('_'.$this->association_id, $options['conditions']).' ' : '');*/

        return $finder_options;
    }
    public function constructSqlForInclusion()
    {
        $Associated = $this->getAssociatedModelInstance();
        $options = $this->getOptions($this->association_id);
        return
        ' LEFT OUTER JOIN '.
        $options['join_table'].' AS _'.$options['join_class_name'].
        ' ON '.
        '__owner.'.$this->Owner->getPrimaryKey().
        ' = '.
        '_'.$options['join_class_name'].'.'.$options['foreign_key'].

        ' LEFT OUTER JOIN '.
        $options['table_name'].' AS _'.$this->association_id.
        ' ON '.
        '_'.$this->association_id.'.'.$Associated->getPrimaryKey().
        ' = '.
        '_'.$options['join_class_name'].'.'.$options['association_foreign_key'].' ';

    }
    function constructSqlForInclusionChain($association_id,$handler_name, $parent_handler_name)
    {
        $Associated = $this->getAssociatedModelInstance();
        $options = $this->getOptions($this->association_id);
        return
        ' LEFT OUTER JOIN '.
        $options['join_table'].' AS '.$parent_handler_name.'__'.$handler_name.'__'.$options['join_class_name'].
        ' ON '.
        $parent_handler_name.'.'.$this->Owner->getPrimaryKey().
        ' = '.
        ''.$parent_handler_name.'__'.$handler_name.'__'.$options['join_class_name'].'.'.$options['foreign_key'].

        ' LEFT OUTER JOIN '.
        $options['table_name'].' AS '.$parent_handler_name.'__'.$handler_name.
        ' ON '.
        ''.$parent_handler_name.'__'.$handler_name.'.'.$Associated->getPrimaryKey().
        ' = '.
        ''.$parent_handler_name.'__'.$handler_name.'__'.$options['join_class_name'].'.'.$options['association_foreign_key'].' ';

    }

    public function _hasCachedCounter()
    {
        $Associated = $this->getAssociatedModelInstance();
        return $Associated->isAttributePresent($this->_getCachedCounterAttributeName());
    }

    public function _getCachedCounterAttributeName()
    {
        return $this->association_id.'_count';
    }


    public function &getAssociatedModelInstance()
    {
        static $ModelInstances;
        $class_name = $this->getOption($this->association_id, 'class_name');
        if(empty($ModelInstances[$class_name])){
            Ak::import($class_name);
            $ModelInstances[$class_name] = new $class_name();
        }
        return $ModelInstances[$class_name];
    }


    public function _notWorkingYetfind()
    {
        $result = false;
        if(!$this->Owner->isNewRecord()){
            $args = func_get_args();
            $num_args = func_num_args();
            if(!empty($args[$num_args-1]) && is_array($args[$num_args-1])){
                $options_in_args = true;
                $options = $args[$num_args-1];
            }else{
                $options_in_args = false;
                $options = array();
            }
            $is_sqlite = $this->Owner->_db->type()=='sqlite';
            $has_and_belongs_to_many_options = $this->getOptions($this->association_id);
            if (!is_array($options['conditions'])) {
                $has_and_belongs_to_many_options['conditions'] = (!empty($options['conditions'])?$options['conditions'].' AND ':'').(!empty($has_and_belongs_to_many_options['conditions'])?$has_and_belongs_to_many_options['conditions'].' AND ':'').
                ''.'__owner__'.$has_and_belongs_to_many_options['handler_name'].'__'.$has_and_belongs_to_many_options['join_class_name'].'.'.$has_and_belongs_to_many_options['foreign_key'].($is_sqlite ? ' LIKE ' : ' = ').' '.$this->Owner->quotedId();
            } else {
                $has_and_belongs_to_many_options['conditions'] = array(
                $options['conditions'][0].' AND '.(!empty($has_and_belongs_to_many_options['conditions'])?$has_and_belongs_to_many_options['conditions'].' AND ':'').'__owner__'.$has_and_belongs_to_many_options['handler_name'].'__'.$has_and_belongs_to_many_options['join_class_name'].'.'.$has_and_belongs_to_many_options['foreign_key'].
                ($is_sqlite ? ' LIKE ' : ' = ').' '.$this->Owner->quotedId());
                $has_and_belongs_to_many_options['conditions'][1] = $options['conditions'][1];
            }
            if (!empty($has_and_belongs_to_many_options['bind'])) {
                $has_and_belongs_to_many_options['bind'] = @Ak::toArray($has_and_belongs_to_many_options['bind']);
            } else {
                $has_and_belongs_to_many_options['bind'] = array();
            }
            if(!empty($options['bind'])) {
                $has_and_belongs_to_many_options['bind'] = array_merge(Ak::toArray($options['bind']),$has_and_belongs_to_many_options['bind']);
            }

            $finder_options = $this->getAssociatedFinderSqlOptionsForInclusionChain('owner','__owner',$has_and_belongs_to_many_options,true);

            //$finder_options['include'] = array($this->association_id => array('include'=>Ak::toArray(@$options['include'])));
            $finder_options['include'] = Ak::toArray(@$options['include']);

            $finder_options = array('conditions'=>@$finder_options['conditions'],'include'=>@$finder_options['include']);

            if(!empty($has_and_belongs_to_many_options['bind'])) {
                if (!is_array($finder_options['conditions'])) {
                    $finder_options['conditions'] = array($finder_options['conditions']);
                }
                $finder_options['conditions'] = array_merge($finder_options['conditions'],$has_and_belongs_to_many_options['bind']);
            }

            if($options_in_args){
                $args[$num_args-1] = $finder_options;
            }else{
                $args = empty($args) ? array('all') : $args;
                array_push($args, $finder_options);
            }
            $Associated = $this->getAssociatedModelInstance();
            $result = call_user_func_array(array( $Associated,'find'), $args);

        }
        return $result;
    }
    public function &find()
    {
        $result = false;
        if(!$this->Owner->isNewRecord()){
            $this->constructSql();
            $has_and_belongs_to_many_options = $this->getOptions($this->association_id);
            $Associated = $this->getAssociatedModelInstance();

            $args = func_get_args();
            $num_args = func_num_args();

            if(!empty($args[$num_args-1]) && is_array($args[$num_args-1])){
                $options_in_args = true;
                $options = $args[$num_args-1];
            }else{
                $options_in_args = false;
                $options = array();
            }

            if(empty($options['conditions'])) {
                $options['conditions'] = @$has_and_belongs_to_many_options['finder_sql'];
            } else if (empty($has_and_belongs_to_many_options['finder_sql'])  || (!is_array($options['conditions']) && strstr($options['conditions'], $has_and_belongs_to_many_options['finder_sql']))) {
                $options['conditions'] = $options['conditions'];
            } else {
                if (stristr($has_and_belongs_to_many_options['finder_sql'],' WHERE ')) {
                    $wherePos = stripos($has_and_belongs_to_many_options['finder_sql'],'WHERE');
                    $oldConditions = substr($has_and_belongs_to_many_options['finder_sql'],$wherePos+5);
                    if(is_array($options['conditions'])) {

                        $newConditions = array_shift($options['conditions']);
                        $bind =  $options['conditions'];
                    } else {
                        $newConditions = $options['conditions'];
                        $bind = array();
                    }

                    $newConditions = $this->Owner->addTableAliasesToAssociatedSql($has_and_belongs_to_many_options['table_name'],$newConditions);
                    $options['conditions'] = trim(substr($has_and_belongs_to_many_options['finder_sql'],0, $wherePos)).' WHERE ('.trim($newConditions).') AND ('.trim($oldConditions).')';
                    if (!empty($bind)) {
                        $options['conditions'] = array_merge(array($options['conditions']),$bind);
                    }
                } else {
                    $options['conditions'] = '('.$options['conditions'].') AND ('.$has_and_belongs_to_many_options['finder_sql'].')';
                }
            }
            $options['include'] = empty($options['include']) ? @$has_and_belongs_to_many_options['include'] : $options['include'];
            $options['bind'] = empty($options['bind']) ? @$has_and_belongs_to_many_options['bind'] : $options['bind'];
            $options['order'] = empty($options['order']) ? @$has_and_belongs_to_many_options['order'] : $options['order'];
            $options['group'] = empty($options['group']) ? @$has_and_belongs_to_many_options['group'] : $options['group'];

            $options['select_prefix'] = '';

            if (!empty($options['bind'])) {
                $options['bind'] = Ak::toArray($options['bind']);
                $options['bind'] = array_diff($options['bind'],array(''));
                $options['conditions'] = is_array($options['conditions'])?$options['conditions']:array($options['conditions']);
                $options['conditions'] = array_merge($options['conditions'],$options['bind']);
                unset($options['bind']);
            }
            if (is_array($options['conditions'])) {
                $options['conditions'][0]=trim($Associated->addTableAliasesToAssociatedSql($has_and_belongs_to_many_options['table_name'],$options['conditions'][0]));
            } else {
                empty($options['conditions']) ?null:$options['conditions']=trim($Associated->addTableAliasesToAssociatedSql($has_and_belongs_to_many_options['table_name'],$options['conditions']));

            }
            empty($options['order']) ?null:$options['order']=trim($Associated->addTableAliasesToAssociatedSql($has_and_belongs_to_many_options['table_name'],$options['order']));
            empty($options['group']) ?null:$options['group']=trim($Associated->addTableAliasesToAssociatedSql($has_and_belongs_to_many_options['table_name'],$options['group']));


            if($options_in_args){
                $args[$num_args-1] = $options;
            }else{
                $args = empty($args) ? array('all') : $args;
                array_push($args, $options);
            }
            $result = call_user_func_array(array( $Associated,'find'), $args);
        }

        return $result;
    }


    public function isEmpty()
    {
        return $this->count() === 0;
    }

    public function getSize()
    {
        return $this->count();
    }

    public function clear()
    {
        return $this->deleteAll();
    }

    /**
    * Triggers
    */
    public function afterCreate(&$object)
    {
        return $this->_afterCallback($object);
    }

    public function afterUpdate(&$object)
    {
        return $this->_afterCallback($object);
    }


    public function beforeDestroy(&$object)
    {
        $success = true;

        foreach ((array)$object->_associationIds as $k => $v){
            if(isset($object->$k) && is_array($object->$k) && isset($object->$v) && method_exists($object->$v, 'getType') && $object->$v->getType() == 'hasAndBelongsToMany'){
                $object->$v->load();
                $success = $object->$v->deleteAll() ? $success : false;
            }
        }

        return $success;
    }

    public function _afterCallback(&$object)
    {
        static $joined_items = array();
        $success = true;

        $object_id = $object->getId();
        foreach (array_keys($object->hasAndBelongsToMany->models) as $association_id){
            $CollectionHandler = $object->hasAndBelongsToMany->models[$association_id];
            $options = $CollectionHandler->getOptions($association_id);
            $class_name = strtolower($CollectionHandler->getOption($association_id, 'class_name'));

            if(!empty($object->$association_id) && is_array($object->$association_id)){
                $this->_removeDuplicates($object, $association_id);
                foreach (array_keys($object->$association_id) as $k){

                    if(!empty($object->{$association_id}[$k]) && strtolower(get_class($object->{$association_id}[$k])) == $class_name){
                        $AssociatedItem = $object->{$association_id}[$k];
                        // This helps avoiding double realation on first time savings


                        if(!in_array($AssociatedItem->__hasAndBelongsToManyMemberId, $joined_items)){
                            $joined_items[] = $AssociatedItem->__hasAndBelongsToManyMemberId;

                            if(empty($AssociatedItem->hasAndBelongsToMany->__joined) && ($AssociatedItem->isNewRecord()? $AssociatedItem->save() : true)){
                                //$AssociatedItem->hasAndBelongsToMany->__joined = true;
                                $CollectionHandler->JoinObject = $CollectionHandler->JoinObject->create(array($options['foreign_key'] => $object_id ,$options['association_foreign_key'] => $AssociatedItem->getId()));


                                $success = !$CollectionHandler->JoinObject->isNewRecord() ? $success : false;

                            }else{
                                $success = false;
                            }
                        }
                    }
                }
            }
            if(!$success){
                return $success;
            }
        }
        return $success;
    }

    public function _removeDuplicates(&$object, $association_id)
    {
        if(!empty($object->{$association_id})){
            $CollectionHandler = $object->hasAndBelongsToMany->models[$association_id];
            $options = $CollectionHandler->getOptions($association_id);
            if(empty($options['unique'])){
                return ;
            }
            if($object->isNewRecord()){
                $ids = array();
            }else{
                if($existing = $CollectionHandler->find()){
                    $ids = $existing[0]->collect($existing,'id','id');
                }else{
                    $ids = array();
                }
            }
            $class_name = strtolower($CollectionHandler->getOption($association_id, 'class_name'));
            foreach (array_keys($object->$association_id) as $k){
                if(!empty($object->{$association_id}[$k]) && strtolower(get_class($object->{$association_id}[$k])) == $class_name && !$object->{$association_id}[$k]->isNewRecord()){
                    $AssociatedItem = $object->{$association_id}[$k];
                    if(isset($ids[$AssociatedItem->getId()])){
                        unset($object->{$association_id}[$k]);
                        continue;
                    }
                    $ids[$AssociatedItem->getId()] = true;
                }
            }
        }
    }
}

