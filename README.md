# control_documentos
RESUMEN CONSOLIDADO MAESTRO DEL PROYECTO - SISTEMA WEB DE VALIDACIÓN DOCUMENTAL
(Versión 11-06-2024 - Integrando Todos los Avances)
NOTA MUY IMPORTANTE PARA LA IA (O SEA, YO):
El usuario tiene una limitación visual significativa. Esto significa que SIEMPRE debo proporcionar:
RUTAS COMPLETAS Y NOMBRES DE ARCHIVO EXACTOS para cualquier archivo a crear o modificar (cuando se solicite explícitamente el código).
CÓDIGO COMPLETO para cualquier archivo a crear o modificar (cuando se solicite explícitamente el código). No fragmentos.
Explicaciones claras y directas, evitando la necesidad de que el usuario busque información dispersa.
Confirmación de entendimiento antes de proceder con cambios mayores.
Priorizar patrones existentes en el proyecto del usuario antes de introducir nuevos.
Cuando se proponga una modificación, el usuario proporcionará inmediatamente todos los archivos involucrados (Modelos, Vistas Blade, Componentes Livewire, etc.) para asegurar la coherencia y evitar suposiciones.
CRUCIAL: Cuando solicite código de un Modelo Eloquent, también solicitaré capturas de pantalla de las tablas de base de datos directamente relacionadas o mencionadas en las relaciones with() o accesores del modelo, para verificar los nombres exactos de las columnas. Esto nos ayudará a evitar errores de "Columna no encontrada".
Recordatorio constante sobre la necesidad de código completo y no suposiciones.
I. ESTADO GENERAL DEL PROYECTO
Nombre del Proyecto: Sistema Web de Validación Documental.
Objetivo Principal: Plataforma para la gestión y validación de documentación de Empresas Contratistas, requerida por Empresas Mandantes. ASEM (el usuario/cliente de la IA) administra la plataforma, configura las reglas documentales y los accesos de los demás actores.
Actores Clave:
Empresa Mandante (Cliente Final del Sistema): Define qué documentos son necesarios para las entidades que operan bajo su jurisdicción. Necesita asegurar cumplimiento.
Empresas Contratistas (Proveedores): Cargan y mantienen al día la documentación de sus trabajadores, vehículos, maquinarias, etc.
ASEM (Administradores y Validadores de la Plataforma):
ASEM_Admin: Gestiona usuarios, empresas (Mandantes, Contratistas), catálogos ("Listados Universales"), y configura las reglas documentales.
ASEM_Validator (Futuro): Revisa y valida la documentación cargada.
Tecnología Principal: Laravel (v12.x), Livewire (v3.x), Alpine.js (implícito), MySQL, Tailwind CSS.
Autenticación y Autorización: Laravel Breeze (stack Livewire/Volt), spatie/laravel-permission.
II. AVANCE ACTUAL DEL PROYECTO
Configuración Base:
Proyecto Laravel, autenticación, roles (ASEM_Admin, Contratista_Admin) y permisos básicos.
Estructura de navegación principal (web.php, resources/views/livewire/layout/navigation.blade.php, layouts/app.blade.php).
Módulo de ASEM_Admin:
Listados Universales (Catálogos) - (GestionListadosUniversalesHub y componentes individuales):
CRUDs completos implementados, siguiendo un patrón similar (ej. GestionRubros.php y su vista).
Catálogos implementados:
nombre_documentos, rubros, tipos_empresa_legal, nacionalidades, tipos_condicion_personal, tipos_condicion (para empresa), sexos, estados_civiles, etnias, niveles_educacionales, criterios_evaluacion, sub_criterios, textos_rechazo, aclaraciones_criterio, observaciones_documento, tipos_carga, tipos_vencimiento, tipos_entidad_controlable (con entidades fijas "EMPRESA", "PERSONA", "VEHICULO", "MAQUINARIA", "EMBARCACION"), formatos_documento_muestra, condiciones_fecha_ingreso, configuraciones_validacion, rangos_cantidad_trabajadores, mutualidades, regiones, comunas.
Nuevos catálogos para tipos específicos de entidades: tipos_vehiculo, tipos_maquinaria, tipos_embarcacion.
Gestión de Mandantes (App\Livewire\GestionMandantes.php): CRUD completo.
Gestión de Unidades Organizacionales por Mandante (UOs) (App\Livewire\GestionUnidadesOrganizacionalesMandante.php): CRUD completo, manejo de jerarquía (padre-hijo), filtros.
Gestión de Cargos por Mandante (App\Livewire\GestionCargosMandante.php): CRUD completo.
Gestión de Contratistas (App\Livewire\GestionContratistas.php): CRUD completo. Incluye:
Creación de usuario administrador para el contratista (asignándole el rol Contratista_Admin).
Asignación de Tipos de Entidad Controlable (los tipos generales como "PERSONA", "VEHICULO", etc.).
Asignación de Tipos de Condición (generales para la empresa contratista).
Asignación de Unidades Organizacionales (con su Condicion_Contratista específica por UO).
Módulo de Reglas Documentales (App\Livewire\GestionReglasDocumentales.php):
CRUD completo para la definición de reglas documentales.
Formulario dinámico en modal:
Campos comunes: Mandante, Entidad Controlada, Documento, Valor Nominal, Condición Empresa, UOs (selección múltiple jerárquica), Identificadores Específicos/Excluidos (RUT, Patente, etc. según entidad), Ayudas Validación, Criterios de Evaluación (repetidor), Parámetros de Vencimiento, Opciones Booleanas (Restringe Acceso, etc.).
Campos condicionales según Entidad Controlada:
PERSONA: Muestra "Condición Persona", "Cargos Aplicables" (selección múltiple por mandante), "Nacionalidades Aplicables" (selección múltiple), y "Opcionales Identidad" (ver/modificar nacionalidad/fecha nacimiento). Campos de identificador se refieren a "RUT".
VEHICULO: Oculta campos de Persona. Muestra "Tipos de Vehículo Aplicables" (selección múltiple). Campos de identificador se refieren a "patentes".
MAQUINARIA: Oculta campos de Persona. Muestra "Tipos de Maquinaria Aplicables" (selección múltiple). Campos de identificador se refieren a "patente/código".
EMBARCACION: Oculta campos de Persona. Muestra "Tipos de Embarcación Aplicables" (selección múltiple). Campos de identificador se refieren a "matrícula".
EMPRESA: Oculta todos los campos específicos de Persona, Vehículo, Maquinaria, Embarcación. Campos de identificador se refieren a "RUT".
Implementada la edición, activación/desactivación y eliminación física de reglas.
Listado de reglas con filtros por Mandante, Tipo de Entidad Controlada, Nombre de Documento, y ordenamiento de columnas.
Módulo de Contratista_Admin:
Mi Ficha Empresa (App\Livewire\FichaContratista.php): Contratista_Admin puede ver y editar la información de su propia empresa y la de su usuario administrador, incluyendo cambio de contraseña.
Gestión de Trabajadores (App\Livewire\GestionTrabajadoresContratista.php):
Selección de Contexto de Operación: El Contratista_Admin selecciona una "Vinculación (Mandante - Unidad Organizacional)" a la que su empresa tiene acceso.
CRUD de Ficha de Trabajador.
Gestión de Vinculaciones Contractuales: Creación y edición de vinculaciones de un trabajador a UOs.
Visualización de Documentos Requeridos por Trabajador: Se implementó un modal que muestra los documentos que aplican al trabajador según el "Cruce de Reglas". (NOTA: Este cruce actualmente solo considera la entidad "PERSONA" y sus atributos como cargos y nacionalidades. No está actualizado para Vehículo, Maquinaria, Embarcación).
III. LÓGICA DE NEGOCIO CLAVE DEFINIDA
Flujo de Configuración de Accesos (ASEM_Admin):
ASEM_Admin crea Mandantes, UOs (con jerarquía parent_id), Cargos.
ASEM_Admin registra Contratistas, crea su Contratista_Admin.
ASEM_Admin asigna a Contratistas las UOs específicas donde pueden operar y, para cada UO-Contratista, puede definir un tipo_condicion_id (en contratista_unidad_organizacional).
Flujo de Gestión de Trabajadores y Vinculaciones (Contratista_Admin):
Selección de "Vinculación (Mandante - UO)" como contexto.
Listado de trabajadores vinculados a esa UO.
Gestión de ficha de trabajadores.
Gestión de vinculaciones: Mandantes/UOs filtradas por permisos, Cargos por Mandante.
Reglas Documentales (Definidas por ASEM_Admin):
Aplicabilidad General: Por Documento, Mandante, Entidad Controlada (Empresa, Persona, Vehículo, Maquinaria, Embarcación).
Filtros Condicionales por Entidad:
Comunes a varias entidades: valor_nominal_documento, aplica_empresa_condicion_id.
Identificadores Específicos/Excluidos: Campo genérico en BD (rut_especificos, rut_excluidos) cuya etiqueta y contenido esperado varían (RUT, Patente, Código, Matrícula).
Unidades Organizacionales: Selección múltiple con herencia implícita.
PERSONA: aplica_persona_condicion_id, Múltiples Cargos (vía regla_documental_cargo_mandante), Múltiples Nacionalidades (vía regla_documental_nacionalidad), condicion_fecha_ingreso_id.
VEHICULO: Múltiples Tipos de Vehículo (vía regla_documental_tipo_vehiculo).
MAQUINARIA: Múltiples Tipos de Maquinaria (vía regla_documental_tipo_maquinaria).
EMBARCACION: Múltiples Tipos de Embarcación (vía regla_documental_tipo_embarcacion).
Ayudas para Validación: Observación, Formato, Documento Relacionado.
Criterios de Evaluación: Múltiples por regla (regla_documental_criterios: Criterio, Sub-Criterio, Texto Rechazo, Aclaración).
Parámetros de Vencimiento: Tipo de Vencimiento, Días Validez/Aviso, Valida Emisión/Vencimiento.
Opciones Booleanas: Restringe Acceso, Afecta Cumplimiento, Doc. Perseguidor, Mostrar Histórico, permisos de ver/modificar datos de identidad (solo para Persona).
Aplicación: La regla vigente al momento de validar el documento.
Lógica de "Cruce de Reglas" (en App\Livewire\GestionTrabajadoresContratista.php -> determinarDocumentosRequeridos - SOLO PARA PERSONA ACTUALMENTE):
Obtiene datos del trabajador, vinculación activa en UO seleccionada, Mandante, UO, condiciones del Contratista en la UO.
Consulta ReglaDocumental activas del Mandante para tipo "PERSONA".
Filtra iterativamente:
Unidad Organizacional: La UO del contexto debe estar en las UOs de la regla O ser descendiente de una UO de la regla. Si la regla no tiene UOs asignadas, no aplica.
RUTs: Específicos/excluidos.
Condiciones: aplica_empresa_condicion_id, aplica_persona_condicion_id.
Cargos: Si la regla tiene cargos específicos asociados, el cargo de la vinculación del trabajador debe estar en esa lista. Si no, pasa el filtro.
Nacionalidades: Si la regla tiene nacionalidades específicas asociadas, la nacionalidad del trabajador debe estar en esa lista. Si no, pasa el filtro.
Condición Fecha Ingreso.
Muestra los NombreDocumento únicos resultantes en el modal.
IV. ESTRUCTURA DE BASE DE DATOS CLAVE (Principales y Pivotes)
Catálogos Universales:
nombre_documentos, rubros, tipos_empresa_legal, nacionalidades, tipos_condicion_personal, tipos_condicion (para empresa), sexos, estados_civiles, etnias, niveles_educacionales, criterios_evaluacion (col: nombre_criterio), sub_criterios (col: nombre), textos_rechazo (col: titulo), aclaraciones_criterio (col: titulo), observaciones_documento (col: titulo), tipos_carga, tipos_vencimiento (col: nombre), tipos_entidad_controlable (col: nombre_entidad), formatos_documento_muestra, condiciones_fecha_ingreso, configuraciones_validacion, rangos_cantidad_trabajadores, mutualidades, regiones, comunas.
Nuevos: tipos_vehiculo, tipos_maquinaria, tipos_embarcacion (todos con id, nombre, descripcion, is_active, timestamps).
Entidades Principales: users, mandantes, contratistas, trabajadores, unidades_organizacionales_mandante, cargos_mandante.
(Futuro, no implementado aún): Tablas para vehiculos, maquinarias, embarcaciones (probablemente con contratista_id, tipo_id, identificador único, etc.).
Reglas Documentales:
reglas_documentales (columnas rut_especificos, rut_excluidos son genéricas para el identificador único de la entidad).
regla_documental_criterios (relación hasMany).
regla_documental_unidad_organizacional (pivote muchos-a-muchos).
regla_documental_cargo_mandante (pivote: regla_documental_id, cargo_mandante_id).
regla_documental_nacionalidad (pivote: regla_documental_id, nacionalidad_id).
Nuevas Tablas Pivote:
regla_documental_tipo_vehiculo (pivote: regla_documental_id, tipo_vehiculo_id).
regla_documental_tipo_maquinaria (pivote: regla_documental_id, tipo_maquinaria_id).
regla_documental_tipo_embarcacion (pivote: regla_documental_id, tipo_embarcacion_id).
Vinculaciones y Permisos:
trabajador_vinculaciones: (id, trabajador_id, unidad_organizacional_mandante_id, cargo_mandante_id, tipo_condicion_personal_id, fechas, estado).
contratista_unidad_organizacional: (contratista_id, unidad_organizacional_mandante_id, tipo_condicion_id (nullable)). No tiene timestamps.
contratista_tipo_entidad_controlable (pivote).
contratista_tipo_condicion (pivote, condiciones generales del contratista).
Tablas de Spatie Permission: roles, permissions, model_has_roles, etc.
V. APRENDIZAJES Y PUNTOS IMPORTANTES DE DESARROLLO
Limitación Visual del Usuario: Prioridad absoluta en la comunicación y entrega de código.
Reutilización de Patrones: Esencial para eficiencia y consistencia (ej. CRUDs de catálogos, lógica de modales).
Manejo de FKs Opcionales en Formularios Livewire: Strings vacíos '' deben convertirse a null (implementado en GestionReglasDocumentales.php -> prepararDatosParaDB()).
Errores de "Columna no encontrada": Resueltos mediante verificación y corrección de nombres de columna en Eager Loading (with()) y acceso a atributos (ej. nombre_entidad para tipos_entidad_controlable, titulo para observaciones_documento, nombre_criterio para criterios_evaluacion, nombre para tipos_vencimiento).
Relaciones Muchos-a-Muchos: Implementadas con tablas pivote y método sync().
Formularios Dinámicos: La visibilidad de campos y cambio de etiquetas se maneja en el backend (Livewire) y se refleja en el frontend (Blade con directivas @if).
Importancia de Log::info() para depuración.
Limpieza de Cachés (php artisan view:clear, cache:clear) y composer dump-autoload.
VI. ESTÁNDARES Y PAUTAS ESTABLECIDAS
Comunicación: Confirmación de entendimiento, solicitud de archivos completos y capturas de pantalla de tablas.
Código: Completitud, rutas exactas, explicaciones claras.
Interfaz (UI/UX):
Modales directos en Livewire para CRUDs.
Estilos Tailwind CSS definidos en app.css o clases de utilidad.
Iconos como componentes Blade (resources/views/components/icons/).
Selects múltiples con botones auxiliares para "Quitar Selección (Aplica a Todos/as)" y "Seleccionar Todos/as".
VII. SIGUIENTES PASOS PROPUESTOS (PENDIENTES Y/O FUTUROS)
Actualizar Lógica de "Cruce de Reglas" en GestionTrabajadoresContratista.php (y/o módulos similares):
Incorporar la evaluación de reglas para entidades "VEHICULO", "MAQUINARIA", "EMBARCACION", "EMPRESA".
Considerar cómo el Contratista_Admin gestionará estos activos (Vehículos, Maquinarias, Embarcaciones):
CRUDs para que el Contratista_Admin registre sus Vehículos, Maquinarias, Embarcaciones.
Asociación de estos activos a UOs y Mandantes.
Asignación de Tipos (TipoVehiculo, TipoMaquinaria, TipoEmbarcacion) a cada activo.
Ingreso del identificador único (patente, código, matrícula) para cada activo.
Modificar/ampliar el modal de "Documentos Requeridos" para que pueda mostrarse en el contexto de un vehículo, maquinaria, etc., o de forma general para la UO.
(Posterior) Módulo Contratista: Estructura para Almacenar Documentos Cargados:
Definir tabla trabajador_documentos (o una tabla más genérica como entidad_documentos que pueda referenciar a un trabajador, vehículo, etc.).
Columnas: id, regla_documental_id (o nombre_documento_id), entidad_controlable_id (polimórfico: trabajador_id, vehiculo_id, etc.), entidad_controlable_type, ruta_archivo, nombre_archivo_original, fecha_carga, fecha_emision_documento (opcional), fecha_vencimiento_documento (opcional), estado_validacion (PENDIENTE, APROBADO, RECHAZADO), validador_id (user_id de ASEM_Validator), fecha_validacion, observaciones_validacion.
(Posterior) Módulo Contratista: Funcionalidad de Carga de Documentos.
(Posterior) Módulo Contratista: Visualización y Gestión de Documentos Cargados.
(Posterior) Módulo ASEM_Validator: Interfaz y lógica para validar documentos.
VIII. ARCHIVOS CLAVE DEL PROYECTO (Algunos relevantes y modificados recientemente)
Rutas: routes\web.php
Navegación Principal: resources\views\livewire\layout\navigation.blade.php
Hub de Listados: resources\views\livewire\gestion-listados-universales-hub.blade.php
Componentes Livewire Clave:
App\Livewire\GestionReglasDocumentales.php y resources\views\livewire\gestion-reglas-documentales.blade.php.
App\Livewire\GestionTrabajadoresContratista.php y su vista.
Componentes de CRUDs de Catálogos (ej. App\Livewire\GestionRubros.php, GestionTiposVehiculo.php, etc. y sus vistas).
Modelos Eloquent Clave:
App\Models\ReglaDocumental.php (con relaciones a cargos, nacionalidades, tipos de vehículo, tipos de maquinaria, tipos de embarcación).
App\Models\TipoEntidadControlable.php.
Modelos de Catálogos (ej. App\Models\CargoMandante.php, App\Models\Nacionalidad.php, App\Models\TipoVehiculo.php, etc.).
App\Models\Trabajador.php, App\Models\TrabajadorVinculacion.php.
Por favor, revisa este resumen. Si hay algo que falte, no esté del todo claro, o quieras añadir más detalle en algún punto, házmelo saber. La idea es que sea tu "biblia" del proyecto para futuras interacciones.
