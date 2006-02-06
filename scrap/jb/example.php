<?php
//
// Definition of eZContentClass class
//
// Created on: <16-Apr-2002 11:08:14 amos>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ publish
// SOFTWARE RELEASE: 3.5.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2006 eZ systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*!
  \class eZContentClass ezcontentclass.php
  \ingroup eZKernel
  \brief Handles eZ publish content classes

  \sa eZContentObject
*/

include_once( 'lib/ezdb/classes/ezdb.php' );
include_once( "kernel/classes/ezpersistentobject.php" );
include_once( "kernel/classes/ezcontentobject.php" );
include_once( "kernel/classes/ezcontentclassattribute.php" );
include_once( "kernel/classes/ezcontentclassclassgroup.php" );
include_once( "kernel/common/i18n.php" );

define( "EZ_CLASS_VERSION_STATUS_DEFINED", 0 );
define( "EZ_CLASS_VERSION_STATUS_TEMPORARY", 1 );
define( "EZ_CLASS_VERSION_STATUS_MODIFED", 2 );

class eZContentClass extends eZPersistentObject
{
    function eZContentClass( $row )
    {
        if ( is_array( $row ) )
        {
            $this->eZPersistentObject( $row );
            $this->VersionCount = false;
            $this->InGroups = null;
            $this->AllGroups = null;
            if ( isset( $row["version_count"] ) )
                $this->VersionCount = $row["version_count"];
        }
        $this->DataMap = false;
    }

    function &definition()
    {
        return array( "fields" => array( "id" => array( 'name' => 'ID',
                                                        'datatype' => 'integer',
                                                        'default' => 0,
                                                        'required' => true ),
                                         "version" => array( 'name' => 'Version',
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "name" => array( 'name' => "Name",
                                                          'datatype' => 'string',
                                                          'default' => '',
                                                          'required' => true ),
                                         "identifier" => array( 'name' => "Identifier",
                                                                'datatype' => 'string',
                                                                'default' => '',
                                                                'required' => true ),
                                         "contentobject_name" => array( 'name' => "ContentObjectName",
                                                                        'datatype' => 'string',
                                                                        'default' => '',
                                                                        'required' => true ),
                                         "creator_id" => array( 'name' => "CreatorID",
                                                                'datatype' => 'integer',
                                                                'default' => 0,
                                                                'required' => true ),
                                         "modifier_id" => array( 'name' => "ModifierID",
                                                                 'datatype' => 'integer',
                                                                 'default' => 0,
                                                                 'required' => true ),
                                         "created" => array( 'name' => "Created",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "remote_id" => array( 'name' => "RemoteID",
                                                               'datatype' => 'string',
                                                               'default' => '',
                                                               'required' => true ),
                                         "modified" => array( 'name' => "Modified",
                                                              'datatype' => 'integer',
                                                              'default' => 0,
                                                              'required' => true ),
                                         "is_container" => array( 'name' => "IsContainer",
                                                                  'datatype' => 'integer',
                                                                  'default' => 0,
                                                                  'required' => true )),
                      "keys" => array( "id", "version" ),
                      "function_attributes" => array( "data_map" => "dataMap",
                                                      'object_count' => 'objectCount',
                                                      'version_count' => 'versionCount',
                                                      'version_status' => 'versionStatus',
                                                      'remote_id' => 'remoteID', // Note: This overrides remote_id field
                                                      'ingroup_list' => 'fetchGroupList',
                                                      'ingroup_id_list' => 'fetchGroupIDList',
                                                      'match_ingroup_id_list' => 'fetchMatchGroupIDList',
                                                      'group_list' => 'fetchAllGroups',
                                                      'creator' => 'creator',
                                                      'modifier' => 'modifier' ),
                      "increment_key" => "id",
                      "class_name" => "eZContentClass",
                      "sort" => array( "id" => "asc" ),
                      "name" => "ezcontentclass" );
    }

    function &clone()
    {
        $row = array(
            "id" => null,
            "version" => $this->attribute( 'version' ),
            "name" => $this->attribute( 'name' ),
            "identifier" => $this->attribute( 'identifier' ),
            "contentobject_name" => $this->attribute( 'contentobject_name' ),
            "creator_id" => $this->attribute( 'creator_id' ),
            "modifier_id" => $this->attribute( 'modifier_id' ),
            "created" => $this->attribute( 'created' ),
            "modified" => $this->attribute( 'modified' ),
            "is_container" => $this->attribute( 'is_container' ) );
        $tmpClass = new eZContentClass( $row );
        return $tmpClass;
    }

    function &create( $userID = false, $optionalValues = array() )
    {
        $dateTime = time();
        if ( !$userID )
            $userID = eZUser::currentUserID();
        $row = array(
            "id" => null,
            "version" => 1,
            "name" => "",
            "identifier" => "",
            "contentobject_name" => "",
            "creator_id" => $userID,
            "modifier_id" => $userID,
            "created" => $dateTime,
            'remote_id' => md5( (string)mt_rand() . (string)mktime() ),
            "modified" => $dateTime,
            "is_container" => 0 );
        $row = array_merge( $row, $optionalValues );
        $contentClass = new eZContentClass( $row );
        return $contentClass;
    }

    /*!
     Creates a new content object instance and stores it.

     \param user ID (optional), current user if not set
     \param section ID (optional), 0 if not set
     \param version number, create initial version if not set
    */
    function &instantiate( $userID = false, $sectionID = 0, $versionNumber = false )
    {
        $attributes =& $this->fetchAttributes();

        if ( $userID === false )
        {
            $user =& eZUser::currentUser();
            $userID =& $user->attribute( 'contentobject_id' );
        }

        $object =& eZContentObject::create( ezi18n( "kernel/contentclass", "New %1", null, array( $this->attribute( "name" ) ) ),
                                            $this->attribute( "id" ),
                                            $userID,
                                            $sectionID );
        $object->store();
        //  $object->setName( "New " . $this->attribute( "name" ) );
        $object->setName( ezi18n( "kernel/contentclass", "New %1", null, array( $this->attribute( "name" ) ) ) );

        if ( !$versionNumber )
        {
            $version =& $object->createInitialVersion( $userID );
        }
        else
        {
            $version =& eZContentObjectVersion::create( $object->attribute( "id" ), $userID, $versionNumber );
        }

        $version->store();

        foreach ( array_keys( $attributes ) as $attributeKey )
        {
            $attribute =& $attributes[$attributeKey];
            $attribute->instantiate( $object->attribute( 'id' ) );
        }

        return $object;
    }

    function canInstantiateClasses()
    {
        $ini =& eZINI::instance();
        $enableCaching = $ini->variable( 'RoleSettings', 'EnableCaching' );

        if ( $enableCaching == 'true' )
        {
            $http =& eZHTTPTool::instance();

            include_once( 'lib/ezutils/classes/ezexpiryhandler.php' );
            $handler =& eZExpiryHandler::instance();
            $expiredTimeStamp = 0;
            if ( $handler->hasTimestamp( 'user-class-cache' ) )
                $expiredTimeStamp = $handler->timestamp( 'user-class-cache' );

            $classesCachedForUser = $http->sessionVariable( 'CanInstantiateClassesCachedForUser' );
            $classesCachedTimestamp = $http->sessionVariable( 'ClassesCachedTimestamp' );
            $user =& eZUser::currentUser();
            $userID = $user->id();

            if ( ( $classesCachedTimestamp >= $expiredTimeStamp ) && $classesCachedForUser == $userID )
            {
                if ( $http->hasSessionVariable( 'CanInstantiateClasses' ) )
                {
                    return $http->sessionVariable( 'CanInstantiateClasses' );
                }
            }
            else
            {
                // store cache
                $http->setSessionVariable( 'CanInstantiateClassesCachedForUser', $userID );
//                $http->setSessionVariable( 'classesCachedTimestamp', mktime() );
            }
        }
        $user =& eZUser::currentUser();
        $accessResult = $user->hasAccessTo( 'content' , 'create' );
        $accessWord = $accessResult['accessWord'];
        $canInstantiateClasses = 1;
        if ( $accessWord == 'no' )
        {
            $canInstantiateClasses = 0;
        }

        if ( $enableCaching == 'true' )
        {
            $http->setSessionVariable( 'CanInstantiateClasses', $canInstantiateClasses );
        }
        return $canInstantiateClasses;
    }

    function &canInstantiateClassList()
    {
        $ini =& eZINI::instance();
        $enableCaching = $ini->variable( 'RoleSettings', 'EnableCaching' );
        if ( $enableCaching == 'true' )
        {
            $http =& eZHTTPTool::instance();
            //$permissionExpired = $http->sessionVariable( 'roleExpired' );
            include_once( 'lib/ezutils/classes/ezexpiryhandler.php' );
            $handler =& eZExpiryHandler::instance();
            $expiredTimeStamp = 0;
            if ( $handler->hasTimestamp( 'user-class-cache' ) )
                $expiredTimeStamp = $handler->timestamp( 'user-class-cache' );

            $classesCachedForUser = $http->sessionVariable( 'CanInstantiateClassesCachedForUser' );
            $classesCachedTimestamp = $http->sessionVariable( 'ClassesCachedTimestamp' );

            $user =& eZUser::currentUser();
            $userID = $user->id();
            if ( ( $classesCachedTimestamp >= $expiredTimeStamp ) && $classesCachedForUser == $userID )
            {
                if ( $http->hasSessionVariable( 'CanInstantiateClassList' ) )
                {
                    return $http->sessionVariable( 'CanInstantiateClassList' );
                }
            }
            else
            {
                $http->setSessionVariable( 'ClassesCachedForUser' , $userID );
                $http->setSessionVariable( 'ClassesCachedTimestamp', mktime() );
            }
        }

        //
        $user =& eZUser::currentUser();
        $accessResult =  $user->hasAccessTo( 'content' , 'create' );
        $accessWord = $accessResult['accessWord'];

        $classIDArray = array();
        $classList = array();
        if ( $accessWord == 'yes' )
        {
            $classList =& eZContentClass::fetchList( EZ_CLASS_VERSION_STATUS_DEFINED, false,false, null, array( 'id', 'name' ) );
            eZDebugSetting::writeDebug( 'kernel-content-class', $classList, "class list fetched from db when access is yes" );

            //          return $classList;
        }
        elseif ( $accessWord == 'no' )
        {
            $classList = array();
//            return array();
        }
        else
        {
            $policies  =& $accessResult['policies'];
            foreach ( array_keys( $policies ) as $policyKey )
            {
                $policy =& $policies[$policyKey];

                $classIDArrayPart = '*';
                if ( isset( $policy['Class'] ) )
                {
                    $classIDArrayPart =& $policy['Class'];
                }

                if ( $classIDArrayPart == '*' )
                {
                    $classList =& eZContentClass::fetchList( EZ_CLASS_VERSION_STATUS_DEFINED, false,false, null, array( 'id', 'name' ) );
                    break;
                }
                else
                {
                    $classIDArray = array_merge( $classIDArray, array_diff( $classIDArrayPart, $classIDArray ) );
                    unset( $classIDArrayPart );
                }
            }

            if( count( $classIDArray ) == 0 && count( $classList ) == 0 )
            {
                $classList = array();
            }
            else if ( count( $classList ) == 0 )
            {
                $classList = array();
                // needs to be optimized
                $db = eZDb::instance();
                $classString = implode( ',', $classIDArray );
                $classList =& $db->arrayQuery( "select id, name from ezcontentclass where id in ( $classString  )  and version = " . EZ_CLASS_VERSION_STATUS_DEFINED );
            }

        }
        eZDebugSetting::writeDebug( 'kernel-content-class', $classList, "class list fetched from db" );
        if ( $enableCaching == 'true' )
        {
            $http->setSessionVariable( 'CanInstantiateClassList', $classList );
        }
        return $classList;
    }

    /*!
     \return The creator of the class as an eZUser object by using the $CreatorID as user ID.
     \note The reference for the return value is required to workaround
           a bug with PHP references.
    */
    function &creator()
    {
        include_once( "kernel/classes/datatypes/ezuser/ezuser.php" );
        $user =& eZUser::fetch( $this->CreatorID );
        return $user;
    }

    /*!
     \return The modifier of the class as an eZUser object by using the $ModifierID as user ID.
     \note The reference for the return value is required to workaround
           a bug with PHP references.
    */
    function &modifier()
    {
        include_once( "kernel/classes/datatypes/ezuser/ezuser.php" );
        $user =& eZUser::fetch( $this->ModifierID );
        return $user;
    }

    /*!
     Find all groups the current class is placed in and returns a list of group objects.
     \return An array with eZContentClassGroup objects.
     \sa fetchGroupIDList()
     \note The reference for the return value is required to workaround
           a bug with PHP references.
    */
    function &fetchGroupList()
    {
        $this->InGroups =& eZContentClassClassGroup::fetchGroupList( $this->attribute( "id" ),
                                                                     $this->attribute( "version" ),
                                                                     true );
        return $this->InGroups;
    }

    /*!
     Find all groups the current class is placed in and returns a list of group IDs.
     \return An array with integers (ids).
     \sa fetchGroupList()
     \note The reference for the return value is required to workaround
           a bug with PHP references.
    */
    function &fetchGroupIDList()
    {
        $list = eZContentClassClassGroup::fetchGroupList( $this->attribute( "id" ),
                                                          $this->attribute( "version" ),
                                                          false );
        $this->InGroupIDs = array();
        foreach ( $list as $item )
        {
            $this->InGroupIDs[] = $item['group_id'];
        }
        return $this->InGroupIDs;
    }

    /*!
     Returns the result from fetchGroupIDList() if class group overrides is
     enabled in content.ini.
     \return An array with eZContentClassGroup objects or \c false if disabled.
     \note \c EnableClassGroupOverride in group \c ContentOverrideSettings from INI file content.ini
           controls this behaviour.
     \note The reference for the return value is required to workaround
           a bug with PHP references.
    */
    function &fetchMatchGroupIDList()
    {
        include_once( 'lib/ezutils/classes/ezini.php' );
        $contentINI =& eZINI::instance( 'content.ini' );
        if( $contentINI->variable( 'ContentOverrideSettings', 'EnableClassGroupOverride' ) == 'true' )
        {
            return $this->attribute( 'ingroup_id_list' );
        }
        else
        {
            return false;
        }
    }

    /*!
     Finds all Class groups in the system and returns them.
     \return An array with eZContentClassGroup objects.
     \sa fetchGroupList(), fetchGroupIDList()
     \note The reference for the return value is required to workaround
           a bug with PHP references.
    */
    function &fetchAllGroups()
    {
        $this->AllGroups =& eZContentClassGroup::fetchList();
        return $this->AllGroups;
    }

    /*!
     \return true if the class is part of the group \a $groupID
    */
    function inGroup( $groupID )
    {
        return eZContentClassClassGroup::classInGroup( $this->attribute( 'id' ),
                                                       $this->attribute( 'version' ),
                                                       $groupID );
    }

    /*!
     \static
     Will remove all temporary classes from the database.
    */
    function removeTemporary()
    {
        $version = EZ_CLASS_VERSION_STATUS_TEMPORARY;
        $temporaryClasses =& eZContentClass::fetchList( $version, true );
        foreach ( $temporaryClasses as $class )
        {
            $class->remove( true, $version );
        }
        eZPersistentObject::removeObject( eZContentClassAttribute::definition(),
                                          array( 'version' => $version ) );
    }

    /*!
     Get remote id of content node
    */
    function &remoteID()
    {
        $remoteID = eZPersistentObject::attribute( 'remote_id', true );
        if ( !$remoteID &&
             $this->Version == EZ_CLASS_VERSION_STATUS_DEFINED )
        {
            $this->setAttribute( 'remote_id', md5( (string)mt_rand() . (string)mktime() ) );
            $this->sync( array( 'remote_id' ) );
            $remoteID = eZPersistentObject::attribute( 'remote_id', true );
        }

        return $remoteID;
    }

    function remove( $remove_childs = false, $version = EZ_CLASS_VERSION_STATUS_DEFINED )
    {
        // If we are not allowed to remove just return false
        if ( !$this->isRemovable() )
            return false;

        if ( is_array( $remove_childs ) or $remove_childs )
        {
            if ( is_array( $remove_childs ) )
            {
                $attributes =& $remove_childs;
                for ( $i = 0; $i < count( $attributes ); ++$i )
                {
                    $attribute =& $attributes[$i];
                    $attribute->remove();
                }
            }
            else
            {
                if ( $version == EZ_CLASS_VERSION_STATUS_DEFINED )
                {
                    $contentObjects =& eZContentObject::fetchSameClassList( $this->ID );
                    foreach ( $contentObjects as $contentObject )
                    {
                        $contentObject->remove();
                    }
                    $contentClassID = $this->ID;
                    $version = $this->Version;
                    $classAttributes =& $this->fetchAttributes( );

                    foreach ( $classAttributes as $classAttribute )
                    {
                        $dataType =& $classAttribute->dataType();
                        $dataType->deleteStoredClassAttribute( $classAttribute, $version );
                    }
                    eZPersistentObject::removeObject( eZContentClassAttribute::definition(),
                                                      array( "contentclass_id" => $contentClassID,
                                                             "version" => $version ) );
                }
                else
                {
                    $contentClassID = $this->ID;
                    $version = $this->Version;
                    $classAttributes =& $this->fetchAttributes( );

                    foreach ( $classAttributes as $classAttribute )
                    {
                        $dataType =& $classAttribute->dataType();
                        $dataType->deleteStoredClassAttribute( $classAttribute, $version );
                    }
                    eZPersistentObject::removeObject( eZContentClassAttribute::definition(),
                                                      array( "contentclass_id" => $contentClassID,
                                                             "version" => $version ) );
                }
            }
        }
        eZPersistentObject::remove();
    }

    /*!
     Checks if the class can be removed and returns \c true if it can, \c false otherwise.
     \sa removableInformation()
    */
    function isRemovable()
    {
        $info = $this->removableInformation( false );
        return count( $info['list'] ) == 0;
    }

    /*!
     Returns information on why the class cannot be removed,
     it does the same checks as in isRemovable() but generates
     some text in the return array.
     \return An array which contains:
             - text - Plain text description why this cannot be removed
             - list - An array with reasons why this failed, each entry contains:
                      - text - Plain text description of the reason.
                      - list - A sublist of reason (e.g from an attribute), is optional.
     \param $includeAll Controls whether the returned information will contain all
                        sources for not being to remove or just the first that it finds.
    */
    function removableInformation( $includeAll = true )
    {
        $result  = array( 'text' => ezi18n( 'kernel/contentclass', "Cannot remove class '%class_name':",
                                         null, array( '%class_name' => $this->Name ) ),
                       'list' => array() );
        $reasons =& $result['list'];
        $db      =& eZDB::instance();

        // Check top-level nodes
        $rows =& $db->arrayQuery( "SELECT ezcot.node_id
FROM ezcontentobject_tree ezcot, ezcontentobject ezco
WHERE ezcot.depth = 1 AND
      ezco.contentclass_id = $this->ID AND
      ezco.id=ezcot.contentobject_id" );
        if ( count( $rows ) > 0 )
        {
            $reasons[] = array( 'text' => ezi18n( 'kernel/contentclass', 'The class is used by a top-level node and cannot be removed.
You will need to change the class of the node by using the swap functionality.' ) );
            if ( !$includeAll )
                return $result;
        }

        // Check class attributes
        $attributes =& $this->fetchAttributes();
        foreach ( $attributes as $key => $attribute )
        {
            $dataType = $attribute->dataType();
            if ( !$dataType->isClassAttributeRemovable( $attribute ) )
            {
                $info = $dataType->classAttributeRemovableInformation( $attribute, $includeAll );
                $reasons[] = $info;
                if ( !$includeAll )
                    return $result;
            }
        }

        return $result;
    }

    function removeAttributes( $attributes = false, $id = false, $version = false )
    {
        if ( is_array( $attributes ) )
        {
            for ( $i = 0; $i < count( $attributes ); ++$i )
            {
                $attribute =& $attributes[$i];
                $attribute->remove();
                $contentObject->purge();
            }
        }
        else
        {
            if ( $version === false )
                $version = $this->Version;
            if ( $id === false )
                $id = $this->ID;
            eZPersistentObject::removeObject( eZContentClassAttribute::definition(),
                                              array( "contentclass_id" => $id,
                                                     "version" => $version ) );
        }
    }

    function adjustAttributePlacements( &$attributes )
    {
        if ( !is_array( $attributes ) )
            return;
        for ( $i = 0; $i < count( $attributes ); ++$i )
        {
            $attribute =& $attributes[$i];
            $attribute->setAttribute( "placement", $i + 1 );
        }
    }

    function store( $store_childs = false )
    {
        if ( is_array( $store_childs ) or $store_childs )
        {
            if ( is_array( $store_childs ) )
                $attributes =& $store_childs;
            else
                $attributes =& $this->fetchAttributes();
            for ( $i = 0; $i < count( $attributes ); ++$i )
            {
                $attribute =& $attributes[$i];
                if ( is_object ( $attribute ) )
                    $attribute->store();
            }
        }

        include_once( 'lib/ezutils/classes/ezexpiryhandler.php' );
        $handler =& eZExpiryHandler::instance();
        $handler->setTimestamp( 'user-class-cache', mktime() );
        $handler->store();

        eZPersistentObject::store();
    }

    /*!
     Initializes this class as a copy of \a $originalClass by
     creating new a new name and identifier.
     It will check if there are other classes already with this name
     in which case it will append a unique number to the name and identifier.
    */
    function initializeCopy( &$originalClass )
    {
        $name = ezi18n( 'kernel/class', 'Copy of %class_name', null,
                        array( '%class_name' => $originalClass->attribute( 'name' ) ) );
        $identifier = 'copy_of_' . $originalClass->attribute( 'identifier' );
        $db =& eZDB::instance();
        $sql = "SELECT count( name ) AS count FROM ezcontentclass WHERE name like '" . $db->escapeString( $name ) . "%'";
        $rows = $db->arrayQuery( $sql );
        $count = $rows[0]['count'];
        if ( $count > 0 )
        {
            ++$count;
            $name .= $count;
            $identifier .= $count;
        }
        $this->setAttribute( 'name', $name );
        $this->setAttribute( 'identifier', $identifier );
        $this->setAttribute( 'created', time() );
        include_once( "kernel/classes/datatypes/ezuser/ezuser.php" );
        $user =& eZUser::currentUser();
        $userID = $user->attribute( "contentobject_id" );
        $this->setAttribute( 'creator_id', $userID );
    }

    /*!
     Stores the current class as a defined version, updates the contentobject_name
     attribute and recreates the class group entries.
     \note It will remove any existing temporary or defined classes before storing.
    */
    function storeDefined( &$attributes )
    {
        eZContentClass::removeAttributes( false, $this->attribute( "id" ), EZ_CLASS_VERSION_STATUS_DEFINED );
        eZContentClass::removeAttributes( false, $this->attribute( "id" ), EZ_CLASS_VERSION_STATUS_TEMPORARY );
        $this->remove( false );
        $this->setVersion( EZ_CLASS_VERSION_STATUS_DEFINED, $attributes );
        include_once( "kernel/classes/datatypes/ezuser/ezuser.php" );
        $user =& eZUser::currentUser();
        $user_id = $user->attribute( "contentobject_id" );
        $this->setAttribute( "modifier_id", $user_id );
        $this->setAttribute( "modified", time() );
        $this->adjustAttributePlacements( $attributes );

        for ( $i = 0; $i < count( $attributes ); ++$i )
        {
            $attribute =& $attributes[$i];
            $attribute->storeDefined();
        }

        // Set contentobject_name to something sensible if it is missing
        if ( count( $attributes ) > 0 )
        {
            $identifier = $attributes[0]->attribute( 'identifier' );
            $identifier = '<' . $identifier . '>';
            if ( trim( $this->attribute( 'contentobject_name' ) ) == '' )
            {
                $this->setAttribute( 'contentobject_name', $identifier );
            }
        }

        // Recreate class member entries
        eZContentClassClassGroup::removeClassMembers( $this->ID, EZ_CLASS_VERSION_STATUS_DEFINED );
        $classgroups =& eZContentClassClassGroup::fetchGroupList( $this->ID, EZ_CLASS_VERSION_STATUS_TEMPORARY );
        for ( $i = 0; $i < count( $classgroups ); $i++ )
        {
            $classgroup =& $classgroups[$i];
            $classgroup->setAttribute( 'contentclass_version', EZ_CLASS_VERSION_STATUS_DEFINED );
            $classgroup->store();
        }
        eZContentClassClassGroup::removeClassMembers( $this->ID, EZ_CLASS_VERSION_STATUS_TEMPORARY );

        include_once( 'lib/ezutils/classes/ezexpiryhandler.php' );
        $handler =& eZExpiryHandler::instance();
        $handler->setTimestamp( 'user-class-cache', mktime() );
        $handler->store();

        eZContentObject::expireAllCache();

        eZPersistentObject::store();
    }

    function setVersion( $version, $set_childs = false )
    {
        if ( is_array( $set_childs ) or $set_childs )
        {
            if ( is_array( $set_childs ) )
                $attributes =& $set_childs;
            else
                $attributes =& $this->fetchAttributes();
            for ( $i = 0; $i < count( $attributes ); ++$i )
            {
                $attribute =& $attributes[$i];
                $attribute->setAttribute( "version", $version );
            }
        }
        eZPersistentObject::setAttribute( "version", $version );
    }

    function exists( $id, $version = EZ_CLASS_VERSION_STATUS_DEFINED, $userID = false, $useIdentifier = false )
    {
        $conds = array( "version" => $version );
        if ( $useIdentifier )
            $conds["identifier"] = $id;
        else
            $conds["id"] = $id;
        if ( $userID !== false and is_numeric( $userID ) )
            $conds["creator_id"] = $userID;
        $version_sort = "desc";
        if ( $version == EZ_CLASS_VERSION_STATUS_DEFINED )
            $conds['version'] = $version;
        $rows =& eZPersistentObject::fetchObjectList( eZContentClass::definition(),
                                                      null,
                                                      $conds,
                                                      null,
                                                      array( "offset" => 0,
                                                             "length" => 1 ),
                                                      false );
        if ( count( $rows ) > 0 )
            return $rows[0]['id'];
        return false;
    }

    function &fetch( $id, $asObject = true, $version = EZ_CLASS_VERSION_STATUS_DEFINED, $user_id = false ,$parent_id = null )
    {

        $conds = array( "id" => $id,
                        "version" => $version );

        if ( $user_id !== false and is_numeric( $user_id ) )
            $conds["creator_id"] = $user_id;

        $version_sort = "desc";
        if ( $version == EZ_CLASS_VERSION_STATUS_DEFINED )
            $version_sort = "asc";
        $rows =& eZPersistentObject::fetchObjectList( eZContentClass::definition(),
                                                      null,
                                                      $conds,
                                                      array( "version" => $version_sort ),
                                                      array( "offset" => 0,
                                                             "length" => 2 ),
                                                      false );

        if ( count( $rows ) == 0 )
            return null;

        $row =& $rows[0];
        $row["version_count"] = count( $rows );
        return new eZContentClass( $row );
    }

    function &fetchByRemoteID( $remoteID, $asObject = true, $version = EZ_CLASS_VERSION_STATUS_DEFINED, $user_id = false ,$parent_id = null )
    {
        $conds = array( "remote_id" => $remoteID,
                        "version" => $version );
        if ( $user_id !== false and is_numeric( $user_id ) )
            $conds["creator_id"] = $user_id;
        $version_sort = "desc";
        if ( $version == EZ_CLASS_VERSION_STATUS_DEFINED )
            $version_sort = "asc";
        $rows =& eZPersistentObject::fetchObjectList( eZContentClass::definition(),
                                                      null,
                                                      $conds,
                                                      array( "version" => $version_sort ),
                                                      array( "offset" => 0,
                                                             "length" => 2 ),
                                                      false );
        if ( count( $rows ) == 0 )
        {
            return null;
        }
        $row =& $rows[0];
        $row["version_count"] = count( $rows );
        return new eZContentClass( $row );
    }

    function &fetchByIdentifier( $identifier, $asObject = true, $version = EZ_CLASS_VERSION_STATUS_DEFINED, $user_id = false ,$parent_id = null )
    {
        $conds = array( "identifier" => $identifier,
                        "version" => $version );
        if ( $user_id !== false and is_numeric( $user_id ) )
            $conds["creator_id"] = $user_id;
        $version_sort = "desc";
        if ( $version == EZ_CLASS_VERSION_STATUS_DEFINED )
            $version_sort = "asc";
        $rows =& eZPersistentObject::fetchObjectList( eZContentClass::definition(),
                                                      null,
                                                      $conds,
                                                      array( "version" => $version_sort ),
                                                      array( "offset" => 0,
                                                             "length" => 2 ),
                                                      false );
        if ( count( $rows ) > 0 )
        {
            $row =& $rows[0];
            $row["version_count"] = count( $rows );
            return new eZContentClass( $row );
        }
        return null;
    }

    /*!
     \static
    */
    function &fetchList( $version = EZ_CLASS_VERSION_STATUS_DEFINED, $asObject = true, $user_id = false,
                         $sorts = null, $fields = null, $classFilter = false, $limit = null )
    {
        $conds = array();
        if ( is_numeric( $version ) )
            $conds["version"] = $version;
        if ( $user_id !== false and is_numeric( $user_id ) )
            $conds["creator_id"] = $user_id;
        if ( $classFilter )
        {
            $classIDCount = 0;
            $classIdentifierCount = 0;

            $classIDFilter = array();
            $classIdentifierFilter = array();
            foreach ( $classFilter as $classType )
            {
                if ( is_numeric( $classType ) )
                {
                    $classIDFilter[] = $classType;
                    $classIDCount++;
                }
                else
                {
                    $classIdentifierFilter[] = $classType;
                    $classIdentifierCount++;
                }
            }

            if ( $classIDCount > 1 )
                $conds['id'] = array( $classIDFilter );
            else if ( $classIDCount == 1 )
                $conds['id'] = $classIDFilter[0];
            if ( $classIdentifierCount > 1 )
                $conds['identifier'] = array( $classIdentifierFilter );
            else if ( $classIdentifierCount == 1 )
                $conds['identifier'] = $classIdentifierFilter[0];
        }

        return eZPersistentObject::fetchObjectList( eZContentClass::definition(),
                                                    $fields,
                                                    $conds,
                                                    $sorts,
                                                    $limit,
                                                    $asObject );
    }

    /*!
     Returns all attributes as an associative array with the key taken from the attribute identifier.
    */
    function &dataMap()
    {
        $map =& $this->DataMap[$this->Version];
        if ( !isset( $map ) )
        {
            $map = array();
            $attributes =& $this->fetchAttributes( false, true, $this->Version );
            foreach ( array_keys( $attributes ) as $attributeKey )
            {
                $attribute =& $attributes[$attributeKey];
                $map[$attribute->attribute( 'identifier' )] =& $attribute;
            }
        }
        return $map;
    }

    function &fetchAttributes( $id = false, $asObject = true, $version = EZ_CLASS_VERSION_STATUS_DEFINED )
    {
        if ( $id === false )
        {
            if ( isset( $this ) and
                 get_class( $this ) == "ezcontentclass" )
            {
                $id = $this->ID;
                $version = $this->Version;
            }
            else
                return null;
        }

        return eZContentClassAttribute::fetchFilteredList( array( "contentclass_id" => $id,
                                                                  "version" => $version ),
                                                           $asObject );
    }

    /*!
     Fetch class attribute by identifier, return null if none exist.

     \param attribute identifier.

     \return Class Attribute, null if none matched
    */
    function &fetchAttributeByIdentifier( $identifier, $asObject = true )
    {
        $attributeArray =& eZContentClassAttribute::fetchFilteredList( array( 'contentclass_id' => $this->ID,
                                                                              'version' => $this->Version,
                                                                              'identifier' => $identifier ), $asObject );
        if ( count( $attributeArray ) == 0 )
        {
            return null;
        }
        return $attributeArray[0];
    }

    function fetchSearchableAttributes( $id = false, $asObject = true, $version = EZ_CLASS_VERSION_STATUS_DEFINED )
    {
        if ( $id === false )
        {
            if ( isset( $this ) and
                 get_class( $this ) == "ezcontentclass" )
            {
                $id = $this->ID;
                $version = $this->Version;
            }
            else
                return null;
        }

        return eZContentClassAttribute::fetchFilteredList( array( "contentclass_id" => $id,
                                                                  "is_searchable" => 1,
                                                                  "version" => $version ) );
    }

    /*!
     \note The reference for the return value is required to workaround
           a bug with PHP references.
    */
    function &versionStatus()
    {

        if ( $this->VersionCount == 1 )
        {
            if ( $this->Version == EZ_CLASS_VERSION_STATUS_TEMPORARY )
                $status = EZ_CLASS_VERSION_STATUS_TEMPORARY;
            else
                $status = EZ_CLASS_VERSION_STATUS_DEFINED;
        }
        else
            $status = EZ_CLASS_VERSION_STATUS_MODIFED;
        return $status;
    }

    /*!
     \deprecated
     \return The version count for the class if has been determined.
     \note The reference for the return value is required to workaround
           a bug with PHP references.
    */
    function &versionCount()
    {
        return $this->VersionCount;
    }

    /*!
     Will generate a name for the content object based on the class
     settings for content object.
    */
    function contentObjectName( &$contentObject, $version = false, $translation = false )
    {

        $contentObjectName = $this->ContentObjectName;
        $dataMap =& $contentObject->fetchDataMap( $version, $translation );

        eZDebugSetting::writeDebug( 'kernel-content-class', $dataMap, "data map" );

        // get all tags to replace
        preg_match_all( "|<[^>]+>|U",
                        $contentObjectName,
                        $tagMatchArray );

        eZDebugSetting::writeDebug( 'kernel-content-class', $tagMatchArray );
        foreach ( $tagMatchArray[0] as $tag )
        {
            $tagName = str_replace( "<", "", $tag );
            $tagName = str_replace( ">", "", $tagName );

            $tagParts = explode( '|', $tagName );

            $namePart = "";
            foreach ( $tagParts as $name )
            {
                // get the value of the attribute to use in name
                if ( isset( $dataMap[$name] ) )
                {
                    $namePart =& $dataMap[$name]->title();
                    if ( $namePart != "" )
                        break;
                }
            }

            // replace tag with object name part
            $contentObjectName =& str_replace( $tag, $namePart, $contentObjectName );
        }
        return $contentObjectName;
    }

    /*!
     \return will return the number of objects published by this class.
    */
    function &objectCount()
    {
        $db =& eZDB::instance();

        $countRow = $db->arrayQuery( 'SELECT count(*) AS count FROM ezcontentobject '.
                                     'WHERE contentclass_id='.$this->ID ." and status = " . EZ_CONTENT_OBJECT_STATUS_PUBLISHED );

        return $countRow[0]['count'];
    }

    /// \privatesection
    var $ID;
    var $Name;
    var $Identifier;
    var $ContentObjectName;
    var $Version;
    var $VersionCount;
    var $CreatorID;
    var $ModifierID;
    var $Created;
    var $Modified;
    var $InGroups;
    var $AllGroups;
    var $IsContainer;
}

?>
