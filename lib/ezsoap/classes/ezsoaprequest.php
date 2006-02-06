<?php
//
// Definition of eZSOAPRequest class
//
// Created on: <19-Feb-2002 15:42:03 bf>
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
  \class eZSOAPRequest ezsoaprequest.php
  \ingroup eZSOAP
  \brief eZSOAPRequest handles SOAP request messages

*/

include_once( "lib/ezutils/classes/ezdebug.php" );
include_once( "lib/ezxml/classes/ezxml.php" );
include_once( "lib/ezsoap/classes/ezsoapparameter.php" );
include_once( "lib/ezsoap/classes/ezsoapenvelope.php" );

class eZSOAPRequest extends eZSOAPEnvelope
{
    /*!
     Constructs a new eZSOAPRequest object. You have to provide the request name
     and the target namespace for the request.

     \param name
     \param namespace
     \param parameters, assosiative array, example: array( 'param1' => 'value1, 'param2' => 'value2' )
    */
    function eZSOAPRequest( $name="", $namespace="", $parameters = array() )
    {
        $this->Name = $name;
        $this->Namespace = $namespace;

        // call the parents constructor
        $this->eZSOAPEnvelope();

        foreach( $parameters as $name => $value )
        {
            $this->addParameter( $name, $value );
        }
    }

    /*!
      Returns the request name.
    */
    function name()
    {
        return $this->Name;
    }

    /*!
      Returns the request target namespace.
    */
    function namespace()
    {
        return $this->Namespace;
    }

    /*!
     Adds a new attribute to the body element.

     \param attribute name
     \param attribute value
     \param prefix
    */
    function addBodyAttribute( $name, $value, $prefix = false )
    {
        $this->BodyAttributes[] = eZDOMDocument::createAttributeNode( $name, $value, $prefix );
    }

    /*!
      Adds a new parameter to the request. You have to provide a prameter name
      and value.
    */
    function addParameter( $name, $value )
    {
        $this->Parameters[] =& new eZSOAPParameter( $name, $value );
    }

    /*!
      Returns the request payload
    */
    function &payload()
    {
        $doc = new eZDOMDocument();
        $doc->setName( "eZSOAP message" );

        $root =& $doc->createElementNodeNS( EZ_SOAP_ENV, "Envelope" );

        $root->appendAttribute( $doc->createAttributeNamespaceDefNode( EZ_SOAP_XSI_PREFIX, EZ_SOAP_SCHEMA_INSTANCE ) );
        $root->appendAttribute( $doc->createAttributeNamespaceDefNode( EZ_SOAP_XSD_PREFIX, EZ_SOAP_SCHEMA_DATA ) );
        $root->appendAttribute( $doc->createAttributeNamespaceDefNode( EZ_SOAP_ENC_PREFIX, EZ_SOAP_ENC ) );

        $root->setPrefix( EZ_SOAP_ENV_PREFIX );

        // add the body
        $body =& $doc->createElementNode( "Body" );
        $body->appendAttribute( $doc->createAttributeNamespaceDefNode( "req", $this->Namespace ) );

        foreach( $this->BodyAttributes as $attribute )
        {
            $body->appendAttribute( $attribute );
        }

        $body->setPrefix( EZ_SOAP_ENV_PREFIX );
        $root->appendChild( $body );

        // add the request
        $request =& $doc->createElementNode( $this->Name );
        $request->setPrefix( "req" );

        // add the request parameters
        foreach ( $this->Parameters as $parameter )
        {
            unset( $param );
            $param =& $this->encodeValue( $parameter->name(), $parameter->value() );
//            $param->setPrefix( "req" );

            if ( $param == false )
                eZDebug::writeError( "Error enconding data for payload", "eZSOAPRequest::payload()" );
            $request->appendChild( $param );
        }

        $body->appendChild( $request );

        $doc->setRoot( $root );
        $ret =& $doc->toString();

        return $ret;
    }

    /*!
      \private
      Encodes the PHP variables into SOAP types.
      TODO: encodeValue(...) in ezsoapresponse.php and ezsoaprequest.php should be moved to a common place,
      e.g. ezsoapcodec.php
    */
    function &encodeValue( $name, $value )
    {
        $returnValue = false;
        switch ( gettype( $value ) )
        {
            case "string" :
            {
                $node =& eZDOMDocument::createElementNode( $name );
                $attr =& eZDOMDocument::createAttributeNode( "type", EZ_SOAP_XSD_PREFIX . ":string" );
                $attr->setPrefix( EZ_SOAP_XSI_PREFIX );
                $node->appendAttribute( $attr );
                $node->appendChild( eZDOMDocument::createTextNode( $value ) );

                $returnValue =& $node;
            } break;

            case "boolean" :
            {
                $node =& eZDOMDocument::createElementNode( $name );
                $attr =& eZDOMDocument::createAttributeNode( "type", EZ_SOAP_XSD_PREFIX . ":boolean" );
                $attr->setPrefix( EZ_SOAP_XSI_PREFIX );
                $node->appendAttribute( $attr );
                if ( $value === true )
                    $node->appendChild( eZDOMDocument::createTextNode( "true" ) );
                else
                    $node->appendChild( eZDOMDocument::createTextNode( "false" ) );
                $returnValue =& $node;
            } break;

            case "integer" :
            {
                $node =& eZDOMDocument::createElementNode( $name );
                $attr =& eZDOMDocument::createAttributeNode( "type", EZ_SOAP_XSD_PREFIX . ":int" );
                $attr->setPrefix( EZ_SOAP_XSI_PREFIX );
                $node->appendAttribute( $attr );
                $node->appendChild( eZDOMDocument::createTextNode( $value ) );

                $returnValue =& $node;
            } break;

            case "double" :
            {
                $node =& eZDOMDocument::createElementNode( $name );
                $attr =& eZDOMDocument::createAttributeNode( "type", EZ_SOAP_XSD_PREFIX . ":float" );
                $attr->setPrefix( EZ_SOAP_XSI_PREFIX );
                $node->appendAttribute( $attr );
                $node->appendChild( eZDOMDocument::createTextNode( $value ) );

                $returnValue =& $node;
            } break;

            case "array" :
            {
                $arrayCount = count( $value );

                $isStruct = false;
                // Check for struct
                $i = 0;
                foreach( $value as $key => $val )
                {
                    if ( $i !== $key )
                    {
                        $isStruct = true;
                        break;
                    }
                    $i++;
                }

                if ( $isStruct == true )
                {
                    $node =& eZDOMDocument::createElementNode( $name );
                    // Type def
                    $typeAttr =& eZDOMDocument::createAttributeNode( "type", EZ_SOAP_ENC_PREFIX . ":SOAPStruct" );
                    $typeAttr->setPrefix( EZ_SOAP_XSI_PREFIX );
                    $node->appendAttribute( $typeAttr );

                    foreach( $value as $key => $val )
                    {
                        $subNode =& $this->encodeValue( $key, $val );
                        $node->appendChild( $subNode );
                        unset( $subNode );
                    }
                    $returnValue =& $node;
                }
                else
                {
                    $node =& eZDOMDocument::createElementNode( $name );
                    // Type def
                    $typeAttr =& eZDOMDocument::createAttributeNode( "type", EZ_SOAP_ENC_PREFIX . ":Array" );
                    $typeAttr->setPrefix( EZ_SOAP_XSI_PREFIX );
                    $node->appendAttribute( $typeAttr );

                    // Array type def
                    $arrayTypeAttr =& eZDOMDocument::createAttributeNode( "arrayType", EZ_SOAP_XSD_PREFIX . ":string[$arrayCount]" );
                    $arrayTypeAttr->setPrefix( EZ_SOAP_ENC_PREFIX );
                    $node->appendAttribute( $arrayTypeAttr );

                    foreach ( $value as $arrayItem )
                    {
                        $subNode =& $this->encodeValue( "item", $arrayItem );
                        $node->appendChild( $subNode );
                        unset( $subNode );
                    }
                    $returnValue =& $node;
                }
            } break;
        }

        return $returnValue;
    }


    /// The request name
    var $Name;

    /// The request target namespace
    var $Namespace;

    /// Additional body element attributes.
    var $BodyAttributes = array();

    /// Contains the request parameters
    var $Parameters = array();
}

?>
