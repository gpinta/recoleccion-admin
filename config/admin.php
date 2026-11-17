<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Grupos LDAP autorizados
    |--------------------------------------------------------------------------
    |
    | Grupos de Active Directory cuyos miembros están autorizados
    | a acceder al Backend Administrativo de Recolección UY.
    |
    | Los DN se obtienen desde variables de entorno para evitar
    | mantener información de infraestructura directamente dentro
    | del código de la aplicación.
    |
    | En esta etapa ambos grupos poseen autorización de acceso.
    |
    | La diferenciación funcional entre operador y administrador
    | será implementada posteriormente mediante autorización
    | específica dentro de Laravel.
    |
    */

    'ldap_groups' => [

        /*
        |--------------------------------------------------------------------------
        | Operadores
        |--------------------------------------------------------------------------
        |
        | Usuarios con rol operativo dentro del Backend Administrativo.
        |
        */

        'operators' => env(
            'ADMIN_LDAP_OPERATORS_GROUP_DN'
        ),


        /*
        |--------------------------------------------------------------------------
        | Administradores
        |--------------------------------------------------------------------------
        |
        | Usuarios con rol administrativo funcional dentro del Backend.
        |
        | Este grupo NO tiene relación con:
        |
        | - Domain Admins
        | - Administrators de Windows
        | - GG_REC_Server_Admins_Windows
        | - GG_REC_Server_Admins_Linux
        |
        */

        'admins' => env(
            'ADMIN_LDAP_ADMINS_GROUP_DN'
        ),

        /*
        |--------------------------------------------------------------------------
        | Lectores
        |--------------------------------------------------------------------------
        |
        | Usuarios con acceso de solo lectura al Backend Administrativo.
        |
        */
        'readers' => env(
            'ADMIN_LDAP_READERS_GROUP_DN'
        ),

    ],

];