#Actualizaciones siteam y funcinamiento real

##descripcion general:
el ssitema e sun sistema de reservas de citas profecional con gestiones, pero el princiaplo objetivo es que sea multi empresa, y multisucursal, es deir que diferentes empresas podran registrarse y gestionar sus propias sucursales, clientes, citas, etc.

##roles:
los sigueintes roles aplicarana l sistema eng enral:
### Superadmin (yo)
### admin (el el dueno de la emporesa por decirlo asi)
### gerente (es el encargado de una sucursal)
### empleado (empleado de una sucursal)

el sistema de roles se basa en esa gerarqui, superadmin administra a todos los admins y clientes, admins administran a todos los gerentes, y gerentes administran a todos los empleados.

cada rol tendra su propio panela dmisnitrativi con diferentes modulos permisos ya cciones para cada rol

## modulos de superadmin:
### Dashboard:
    vermos metricas avanzadas de todo els sitema, como conteo de empresas, clientes, empresas activas/inactivas, metricas dels sitema como estao del servidor, ancho de banda usado, espacio usado etc, tambien un historial de movimientos o acciones ya implementado como: actualizacion de suaurio xx, eliminacion de usuario xxx, eliminacion de emporesa xxx, eliminaicon de sucursa xxx, etc.
### empreas:
    aqui podremos gestionar CRUD de todas las empreass actuales.

### planes:
    crud de planes dels sitema

### mensajes:
     este e sun modulo donde el super admin podra enivar mensajes internosa  todos los admins dels istema es decir a todos los duenos de empresa so elegir uno idividualmetne, es uns ervicion de mensajeria de solo una via e sdecir solo s epodrane nviar mas no recibir, y los emnsajes se enviaran por geraquias, es decir superadmin le puede enviar a todos, admin de emrpesa s sus gerentes, y genrrtetes a sus empelados.

### usuarios
    crud de todos los usaurios registrados, con filtroa para rol aqui se veran los usaurios que las empresas creen es decir sus empleados, gerente etc.

### ajustes:
    aqui adminsitraremos la configiracion genral del siste, como estadoa ctivo del sistema, en amnteimieno, permitir login, registros, credenciasles para smtp y cosas quea fecten al sistema en genral no a una empresa.


## modulos para admin:

### dashboard
    aqui veremos metricas avanzada syc omparacions con meses anteriores, de cosas como sucursales conteo, conteo de empleados, citas, clientes, ganacias (en base a servicios completados), citas no compeltadas, citas pendientes, citas completadas,  empleado con mas citas empleado con menos citas horas mas solcitadas etc.
### sucursales:
    aqui sera un crud de sucursales de cada empresa, aqui podremos gestionar CRUD de todas las sucursales actuales.
### empleados/usuraios:
    aqui sera crud de empleados de cada empresa, aqui podremos gestionar CRUD de todos los empleados actuales y asignarloa a una susucrsal, gestioanr sus horas libres, sus dias de cescandos y si cobran un porcetaje mas por cada servisio (por ejemplo si es un senio podria cobrar un poco mas por algun servicio). 
    al edita run empleado habra un checkbox que dira visible en home page, sie ste esa ctivo se mostrara en el home page de la empresa (maximo 4 empleados se mostrarane ne l hoem page, mostraremoss u nombre, puesto y foto).
    el admin podra signar roles: empleado, gerente y admin.
    ela dmin podra asignar cada empleado, gernte a una sucursal, solo pueden pertenecer a una sucursal.
### citas:
    aqui sera crud de citas de cada empresa, aqui podremos gestionar CRUD de todas las citas actuales, crea nuea signarle ua horam fecha,e ,peados ervicio etc.
### clientes
    aqui sera R de clientes de cada empresa, aqui podremos gestionar R de todos los clientes actuales correspndientes a esta empresa, no se podran eliminar solo leer (solo superadmin puede eliminar clientes, ya que un cliente puede eprtenecer a diferentees empesas, segun el slug que visite).
### Blog:
    Crud de entradas par ale blog de esta empresa, utulizaremos para crear un nueov blog u editro avanzado que nos eprmita agregar formatos como negritas, cursivas enlace simgens etc, solciitaremos titulo, contenido, slug(puede crearse en base al titulo siempre tratendod e que sea unico) etc, para.
    al editar un lbog agrgaremos un checkbox que dira visible en home page, sie ste esa ctivo se mostrara en el home page de la empresa (maximo 3 articulso se mostrarane ne l hoem page).
### resenas:
     aqui ela dmin gestioanra crud las resenas que los clientes le hayan dejado, al editarlas se motrara un checkbox que dira visible en home page, sie ste esa ctivo se mostrara en el home page de la empresa (maximo 4 resenas se mostrarane ne l hoem page).
### home page:
    aqui el admin podra gestioanr las secciones que se mostraran ens u home page se mostrarn las secicones del home apge por seccion para que modificque cada parte, cad una con su boton de encedido apagado.
    
    #### secciones del home page modificabls:
        1 Hero: tendra titulo, descripcion, iamgens (5 max), bootond e accion 1(texto a mostrar y url interna oe externa), boton 2(texto a mostrar y url interna oe externa).
        2. Nuestra Misión y Visión: son dos cuatros mision y vision aqui solo podra modificar la descripcion para cada cuadro.
        3. Nuestro Blog: aqui podra elegir 3 articulos para mostrar en el home page.
        4. Nuestro Equipo: aqui podra elegir 4 empleados para mostrar en el home page
        5. Nuestros Servicios: aqui podra elegir 4 servicios para mostrar en el home page
        6. Nuestros Equipos: aqui podra elegir 4 miembros para mostrar en el home page
        7.  Contáctanos: aqui podra colcoar enlacesa  redes sociales, correo, telefono direccione tc, solo los campos que se rellenen se msotrarn en la aprte de contacto, e iguale stos enlaces s emostrarane el footer.
### ajustes:
    aqui ela dmin de cada empresa pora gestioanr ajsutes avanzados de su empresa, como definir nombre, logo, slogan, desactivar temporalmetne su empresa, desactivar registrod e cleintes(si no quiere registrar cleintes), desactivar acceso par todos los usaurios correspondiente sa esta emprtesa.
    color primario, color secundario.

## modulos para gernte:
    aqui seran los mismos que admin pero la limitacion que solo seran para la sucursal aa la que coresponde, pero tampoco vera los modulos, lbog, resenas, home apge, ni clientes, y su dashboar se bsara solo en su sucursal

## modulos para empleado:
    aqui seran los mismos que gerente pero la limitacion que solo seran para la sucursal aa la que coresponde, pero tampoco vera los modulos, lbog, resenas, home apge, ni clientes. y su dshboar se basara solo en el es decir sus propias metricas y comparaciones como comparacion de citas compeltadas el emsa nteiro con hoy, cuantas citas ya lelga cfinalizadas cauntas tiene caneladas etc.

## modulos para clientes:
    los cleintes registrados tendran sus propios modulos:
### citas:
    aqui podran ver su historial de citas, agendar de nuevo (cloando sucursa, servicio y empledao, tendr que regirigirse al motor de cracion de citas con esos datos ya seleccionadod para solo colcoar fehca hora y confirmarcion de cita)
### ajustes:
    aqui podran ajustar su perfil, como nombre, telefono, correo, informaicno eprsonal y cambio de contrasea, esto tambien va enlos ajustes de los demas roles a excpcion del superadmin.


## estrucutrra de rutas y carpetas:
 la estrucutra es las igueinte:

### para vistas y backend administrativas de super admin
    /sadmin/dahsboard.php
    /sadmin/empresas.php
    /sadmin/usuarios.php
    /sadmin/ajustes.php
    ...... demas modulos
    y para las api o acciones del abckend:
    /sadmin/api/dahsboard.php
    /sadmin/api/empresas.php
    /sadmin/api/usuarios.php
    /sadmin/api/ajustes.php
    ...demas modulo api
### para vistas y backend administrativas de admin
    /admin/dahsboard.php
    /admin/empresas.php
    /admin/usuarios.php
    /admin/ajustes.php
    ...... demas modulos
    y para las api o acciones del abckend:
    /admin/api/dahsboard.php
    /admin/api/empresas.php
    /admin/api/usuarios.php
    /admin/api/ajustes.php
    ....demas api
### para vistas y backend administrativas de gerente
    seranl as mismas que admin pero tomara su rol para definicr acciones y ver sus persimos, mostrar mosulods etc
### para vistas y backend administrativas de empleado
    seranl as mismas que admin pero tomara su rol para definicr acciones y ver sus persimos, mostrar mosulods etc
### para vistas y backend administrativas de cliente
    /cadmin/citas.php
    /cadmin/ajustes.php



### para vistas publicas (lo que ve una persona no logueadad en els isteam correpsodiente a cad empresa) 
/public/inicio.php (este se construira dinamicamente con las configuraciones que el admin de esta empresa coloco en su modulo home_page)
/public/agendar_cita.php (motor publico de creacion de citas)
/public/blog.php
/public/sedes.php
/public/servicios.php
/public/contacto.php
para ifdentificar que emporesa queremos ver se insertar el parametro slug_empresa (que este identificara a la empredsa para msotrar su infoamcion)


actaulmente ya hay varios modulos creados y todo pero hayq ue corregir las vistas y carpeta spara no mezcalr cada ccosa