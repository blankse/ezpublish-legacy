<?php
//
// Created on: <03-May-2002 15:17:01 bf>
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

include_once( 'kernel/classes/ezcontentobject.php' );
include_once( 'kernel/classes/ezcontentclass.php' );
include_once( 'kernel/classes/ezcontentobjectversion.php' );
include_once( 'kernel/classes/ezcontentobjectattribute.php' );
include_once( 'kernel/classes/ezcontentbrowse.php' );


include_once( 'lib/ezutils/classes/ezhttptool.php' );
include_once( 'lib/ezlocale/classes/ezlocale.php' );

include_once( 'kernel/common/template.php' );

$ObjectID = $Params['ObjectID'];
$EditVersion = $Params['EditVersion'];
$EditLanguage = $Params['EditLanguage'];
// Will be sent from the content/edit page and should be kept
// incase the user decides to continue editing.
$FromLanguage = $Params['FromLanguage'];

$http =& eZHTTPTool::instance();

$redirection = false;
if ( $Module->isCurrentAction( 'EditObject' ) )
{
    $redirection = array( 'view' => 'edit',
                          'parameters' => array( $ObjectID, $EditVersion, $EditLanguage, $FromLanguage ),
                          'unordered_parameters' => null );
}

$translateToLanguage = false;
$activeTranslation = false;
$activeTranslationLocale = false;

if ( $http->hasPostVariable( 'TranslationLanguageEdit' ) )
{
    $translateToLanguage = $http->postVariable( 'TranslationLanguageEdit' );
    $activeTranslation = $translateToLanguage;
    $activeTranslationLocale =& eZLocale::instance( $activeTranslation );
}

if ( $Module->isCurrentAction( 'EditLanguage' ) and  $Module->hasActionParameter( 'SelectedLanguage' ) )
{
    $translateToLanguage = $Module->actionParameter( 'SelectedLanguage' );
}

if ( $EditLanguage )
{
    $translateToLanguage = $EditLanguage;
}

$createLanguage = false;
if ( $Module->isCurrentAction( 'AddLanguage' ) and
     $Module->hasActionParameter( 'SelectedLanguage' ) )
{
    $createLanguage = $Module->actionParameter( 'SelectedLanguage' );
}

$tpl =& templateInit();

$object =& eZContentObject::fetch( $ObjectID );

if ( $object === null  )
    return $Module->handleError( EZ_ERROR_KERNEL_NOT_AVAILABLE, 'kernel' );

if ( !$object->attribute( 'can_edit' ) )
    return $Module->handleError( EZ_ERROR_KERNEL_ACCESS_DENIED, 'kernel' );

if ( !$object->attribute( 'can_translate' ) )
    return $Module->handleError( EZ_ERROR_KERNEL_ACCESS_DENIED, 'kernel' );

$version =& $object->version( $EditVersion );

if ( $version === null  )
    return $Module->handleError( EZ_ERROR_KERNEL_NOT_AVAILABLE, 'kernel' );

$isRemoveActive = false;
$removeLanguageArray = false;
if ( ( $Module->isCurrentAction( 'RemoveLanguage' ) or
       $Module->isCurrentAction( 'RemoveLanguageConfirmation' ) ) and
     $Module->hasActionParameter( 'SelectedLanguageList' ) )
{
    $isRemoveActive = true;
    $removeLanguageList = $Module->actionParameter( 'SelectedLanguageList' );
    if ( $Module->isCurrentAction( 'RemoveLanguageConfirmation' ) )
    {
        foreach ( $removeLanguageList as $removeLanguage )
        {
            $version->removeTranslation( $removeLanguage );
        }
        $isRemoveActive = false;
    }
    else
    {
        $removeLanguageArray = array();
        foreach ( $removeLanguageList as $removeLanguage )
        {
            $removeLanguageArray[] = new eZContentObjectTranslation( $ObjectID, $EditVersion, $removeLanguage );
        }
    }
}

$classID = $object->attribute( 'contentclass_id' );
$class =& eZContentClass::fetch( $classID );
$originalContentAttributes =& $version->contentObjectAttributes();
$originalLocale =& eZLocale::instance( eZContentObject::defaultLanguage() );

$translateContentAttributes = false;
$translateContentMap = false;
$translateLocale = false;

if ( $createLanguage !== false )
{
    // Create a new language
    unset( $translateContentAttributes );
//     $translateContentAttributes = $originalContentAttributes;
    $translateContentAttributes = array();
    foreach ( array_keys( $originalContentAttributes ) as $contentAttributeKey )
    {
        $originalContentAttribute =& $originalContentAttributes[$contentAttributeKey];
        $contentAttribute =& $originalContentAttribute->translateTo( $createLanguage );
        $contentAttribute->sync();
        $translateContentAttributes[] =& $contentAttribute;
    }
//    $translateContentAttributes =& $version->contentObjectAttributes( $translateToLanguage );
}

if ( $translateToLanguage !== false )
{
    $translateContentAttributes =& $version->contentObjectAttributes( $translateToLanguage );
    if ( $translateContentAttributes === null or
         count( $translateContentAttributes ) == 0 )
        $translateToLanguage = false;
}

$redirectionAllowed = true;
$unvalidatedAttributes = false;
if ( $translateToLanguage !== false )
{
    $translateLocale =& eZLocale::instance( $translateToLanguage );

    $translateContentMap = array();
    foreach ( array_keys( $translateContentAttributes ) as $contentAttributeKey )
    {
        $contentAttribute =& $translateContentAttributes[$contentAttributeKey];
        $translateContentMap[$contentAttribute->attribute( 'contentclassattribute_id' )] =& $contentAttribute;
    }

    foreach ( array_keys( $originalContentAttributes ) as $originalContentAttributeKey )
    {
        $originalContentAttribute =& $originalContentAttributes[$originalContentAttributeKey];
        $originalContentAttributeID = $originalContentAttribute->attribute( 'contentclassattribute_id' );
        if ( !isset( $translateContentMap[$originalContentAttributeID] ) )
            $translateContentMap[$originalContentAttributeID] = false;
    }
}

if ( $activeTranslation )
{
    // Custom Action Code Start
    $customAction = false;
    $customActionAttributeArray = array();
    // Check for custom actions
    if ( $http->hasPostVariable( "CustomActionButton" ) )
    {
        $customActionArray = $http->postVariable( "CustomActionButton" );
        foreach ( $customActionArray as $customActionKey => $customActionValue )
        {
            $customActionString = $customActionKey;

            if ( preg_match( "#^([0-9]+)_(.*)$#", $customActionString, $matchArray ) )
            {
                $customActionAttributeID = $matchArray[1];
                $customAction = $matchArray[2];
                $customActionAttributeArray[$customActionAttributeID] = array( 'id' => $customActionAttributeID,
                                                                               'value' => $customAction );
            }
        }
    }
    // Custom Action Code End

    $storeActions = array( 'Store', 'EditObject', 'AddLanguage', 'RemoveLanguage', 'EditLanguage' );

    $inputValidated = true;
    $storeRequired = in_array( $Module->currentAction(), $storeActions );

    if ( $storeRequired  || $http->hasPostVariable( 'CustomActionButton' ) )
    {
        $defaultLanguage = $object->defaultLanguage();
        $unvalidatedAttributes = array();
        foreach ( array_keys( $translateContentAttributes ) as $translateContentAttributeKey )
        {
            $contentObjectAttribute =& $translateContentAttributes[$translateContentAttributeKey];
            $contentClassAttribute =& $contentObjectAttribute->contentClassAttribute();

            // Check if this is a translation
            $currentLanguage = $contentObjectAttribute->attribute( 'language_code' );

            $isTranslation = false;
            if ( $currentLanguage != $defaultLanguage )
                $isTranslation = true;

            // If current attribute is a translation
            // Check if this attribute can be translated
            // If not do not validate, since the input will be copyed from the original
            $doNotValidate = false;
            if ( $isTranslation )
            {
                if ( !$contentClassAttribute->attribute( 'can_translate' ) )
                    $doNotValidate = true;
            }

            if ( $doNotValidate == false )
            {
                $dataType =& $contentClassAttribute->dataType();
                $dataProperties = $dataType->attribute( 'properties' );
                if ( $dataProperties['translation_allowed'] )
                {
                    $inputParameters = array();
                    if ( $contentObjectAttribute->validateInput( $http, 'ContentObjectAttribute', $inputParameters ) == false )
                    {
                        eZDebug::writeDebug( 'Validating ' . $contentObjectAttribute->attribute( 'id' ) . ' failed' );
                        $inputValidated = false;
                        $unvalidatedAttributes[] = array( 'identifier' => $contentClassAttribute->attribute( 'identifier' ),
                                                          'name' => $contentClassAttribute->attribute( 'name' ),
                                                          'description' => $contentObjectAttribute->attribute( 'validation_log' ),
                                                          'id' => $contentObjectAttribute->attribute( 'id' ) );
                    }
                    else
                    {
                        eZDebug::writeDebug( 'Validating ' . $contentObjectAttribute->attribute( 'id' ) . ' success' );
                    }
                    $contentObjectAttribute->fetchInput( $http, 'ContentObjectAttribute' );

                }
            }
        }

        if ( $inputValidated )
        {
            foreach ( array_keys( $translateContentAttributes ) as $translateContentAttributeKey )
            {
                $contentObjectAttribute =& $translateContentAttributes[$translateContentAttributeKey];
                $contentClassAttribute =& $contentObjectAttribute->contentClassAttribute();

                // Check if this is a translation
                $currentLanguage = $contentObjectAttribute->attribute( 'language_code' );

                $isTranslation = false;
                if ( $currentLanguage != $defaultLanguage )
                    $isTranslation = true;

                // If current attribute is a translation
                // Check if this attribute can be translated
                // If not do not store, since the input will be copyed from the original
                $doNotStore = false;
                if ( $isTranslation )
                {
                    if ( !$contentClassAttribute->attribute( 'can_translate' ) )
                        $doNotStore = true;
                }

                if ( $doNotStore == false )
                {
                    $dataType =& $contentClassAttribute->dataType();
                    $dataProperties = $dataType->attribute( 'properties' );
                    if ( $dataProperties['translation_allowed'] )
                    {
                        $contentObjectAttribute->store();
                    }
                }
            }


            foreach( array_keys( $translateContentAttributes ) as $translateContentAttributeKey )
            {
                $contentObjectAttribute =& $translateContentAttributes[$translateContentAttributeKey];

                $isRequired =& $contentObjectAttribute->attribute( 'is_required' );
                $content    =& $contentObjectAttribute->attribute( 'content' );
                $contentClassAttribute =& $contentObjectAttribute->attribute( 'contentclass_attribute' );
                if ( $isRequired && !$content ) // input not validated since a required field is empty
                {

                    $unvalidatedAttributes[] = array( 'identifier' => $contentClassAttribute->attribute( 'identifier' ),
                                                      'name' => $contentClassAttribute->attribute( 'name' ),
                                                      'description' => $contentObjectAttribute->attribute( 'validation_log' ),
                                                      'id' => $contentObjectAttribute->attribute( 'id' ) );
                }

                // Check if this is a translation
                $currentLanguage = $contentObjectAttribute->attribute( 'language_code' );

                $isTranslation = false;
                if ( $currentLanguage != $defaultLanguage )
                    $isTranslation = true;

                // If current attribute is a translation
                // Check if this attribute can be translated
                // If not do not store, since the input will be copyed from the original
                $doNotStore = false;
                if ( $isTranslation )
                {
                    if ( !$contentClassAttribute->attribute( 'can_translate' ) )
                        $doNotStore = true;
                }

                if ( $doNotStore == false )
                {
                    if ( !isset( $currentRedirectionURI ) )
                        $currentRedirectionURI = $Module->redirectionURI( 'content', 'translate', array( $ObjectID, $EditVersion ) );
                    $object->handleCustomHTTPActions( $contentObjectAttribute, 'ContentObjectAttribute',
                                                      $customActionAttributeArray,
                                                      array( 'module' => &$Module,
                                                             'current-redirection-uri' => $currentRedirectionURI ) );
                    $contentObjectAttribute->setContent( $contentObjectAttribute->attribute( 'content' ) );
                }
            }

            $object->store();
        }

        if ( !$inputValidated )
        {
            $isRemoveActive = false;
            $redirectionAllowed = false;
        }
    }
}

if ( $redirectionAllowed and
     $redirection !== false )
{
    if ( is_string( $redirection ) )
        return $Module->redirectTo( $redirection );
    else if ( is_array( $redirection ) )
        return $Module->redirectToView( $redirection['view'], $redirection['parameters'], $redirection['unordered_parameters'] );
}






$tpl->setVariable( 'object', $object );
$tpl->setVariable( 'edit_version', $EditVersion );
$tpl->setVariable( 'edit_language', $EditLanguage );
$tpl->setVariable( 'from_language', $FromLanguage );
$tpl->setVariable( 'content_version', $version );
$tpl->setVariable( 'translation_language', $translateToLanguage );
$tpl->setVariable( 'translation_locale', $translateLocale );
$tpl->setVariable( 'original_locale', $originalLocale );

$tpl->setVariableRef( 'content_attributes', $originalContentAttributes );
$tpl->setVariableRef( 'content_attributes_language', $translateContentAttributes );
$tpl->setVariableRef( 'content_attribute_map', $translateContentMap );

$tpl->setVariable( 'validation', array( 'attributes' => $unvalidatedAttributes,
                                        'processed' => is_array( $unvalidatedAttributes ),
                                        'language_code' => $activeTranslation,
                                        'locale' => $activeTranslationLocale ) );

$tpl->setVariable( 'is_remove_active', $isRemoveActive );
$tpl->setVariable( 'remove_language_list', $removeLanguageArray );

$Result = array();
$Result['content'] =& $tpl->fetch( 'design:content/translate.tpl' );
$Result['path'] = array( array( 'text' => ezi18n( 'kernel/content', 'Translate' ),
                                'url' => false ),
                         array( 'text' => $object->attribute( 'name' ),
                                'url' => false ) );

?>
