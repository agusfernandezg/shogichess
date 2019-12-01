# shogichess

**Ajedrez Japonés**

![image](https://drive.google.com/uc?export=view&id=1nqXVIV4Lu4JoVuOzysBuRo70Lx9xS7dz)


**UML**

![image](https://drive.google.com/uc?export=view&id=1PLoXBUng2I7vtkxHX9QvtXJAfrnuODbS)


**Descripcíón:**

  El proyecto está desarrolado en Symfony 4. Para persistir los datos se usa Doctrine junto con una base de datos Sqlite.

**Funcionalidades desarroladas:**

- Validación de movimientos.
- Manejo del turno de cada equipo.
- Promoción de piezas.
- Re-introducir piezas al tablero.
- Jaque.
- Jaque Mate.

**Pasos para instalar el proyecto**

1. git clone https://github.com/agusfernandezg/shogichess.git
2. Tener instalardo Composer, Yarn y Nodejs.
3. ejecutar el siguiente comando en el directorio raiz de la instalación: **composer install**
4. ejecutar el siguiente comando en el directorio raiz de la instalación: **yarn install**
5. Finalmente, ejecutar: **php bin/console server:run**


**Pruebas Unitarias**

Comando: ./bin/phpunit
