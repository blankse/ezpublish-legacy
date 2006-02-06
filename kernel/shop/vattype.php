<?php
//
// Definition of  class
//
// Created on: <25-Nov-2002 15:40:10 wy>
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

include_once( "kernel/common/template.php" );
include_once( "kernel/classes/ezvattype.php" );
include_once( "lib/ezutils/classes/ezhttppersistence.php" );

$module =& $Params["Module"];

$http =& eZHttpTool::instance();

$vatTypeArray =& eZVatType::fetchList();

if ( $http->hasPostVariable( "AddVatTypeButton" ) )
{
    $vatType =& eZVatType::create();
    $vatType->store();
    $module->redirectTo( $module->functionURI( "vattype" ) . "/" );
    return;
}

if ( $http->hasPostVariable( "SaveVatTypeButton" ) )
{
    foreach ( $vatTypeArray as $vatType )
    {
        $id = $vatType->attribute( 'id' );
        if ( $http->hasPostVariable( "vattype_name_" . $id ) )
        {
            $name = $http->postVariable( "vattype_name_" . $id );
        }
        if ( $http->hasPostVariable( "vattype_percentage_" . $id ) )
        {
            $percentage = $http->postVariable( "vattype_percentage_" . $id );
        }
        $vatType->setAttribute( 'name', $name );
        $vatType->setAttribute( 'percentage', $percentage );
        $vatType->store();
    }
    $module->redirectTo( $module->functionURI( "vattype" ) . "/" );
    return;
}

if ( $http->hasPostVariable( "RemoveVatTypeButton" ) )
{
    $vatTypeIDList = $http->postVariable( "vatTypeIDList" );

    foreach ( $vatTypeIDList as $vatTypeID )
    {
        eZVatType::remove( $vatTypeID );
    }
    $module->redirectTo( $module->functionURI( "vattype" ) . "/" );
    return;
}

$tpl =& templateInit();
$tpl->setVariable( "vattype_array", $vatTypeArray );
$tpl->setVariable( "module", $module );

$path = array();
$path[] = array( 'text' => ezi18n( 'kernel/shop', 'VAT types' ),
                 'url' => false );


$Result = array();
$Result['path'] =& $path;
$Result['content'] =& $tpl->fetch( "design:shop/vattype.tpl" );

?>
