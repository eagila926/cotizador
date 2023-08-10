from django.db import models

# Create your models here.
class Usuario(models.Model):
    cod_user = models.TextField(verbose_name='Codigo', unique=True)
    nombre = models.TextField(verbose_name='Nombre')
    apellido = models.TextField(verbose_name='Apellido')
    correo = models.TextField(verbose_name='Correo')
    tipo = models.TextField(verbose_name='Tipo')
    ciudad = models.TextField(verbose_name='Ciudad')
    pais = models.TextField(verbose_name='Pais')

    def _str_(selfs):
        return selfs.cod_user
    
    class Meta:
        verbose_name = 'Usuario'
        verbose_name_plural = 'Usuarios'
        ordering: ['cod_user']
    

class Inventario(models.Model):
    cod_inven = models.IntegerField(verbose_name=("Codigo Inventario"), primary_key=True, max_length=5)
    descripcion = models.TextField(verbose_name='Descripcion')
    valor_costo = models.DecimalField(max_digits=10, decimal_places=4, verbose_name='Valor Costo')
    valor_venta = models.DecimalField(max_digits=10, decimal_places=4, verbose_name='Valor Venta')
    unidad_compra = models.TextField(max_length=3,verbose_name='Unidad Compra')
    stock = models.DecimalField(max_digits=15, decimal_places=4, verbose_name='Stock')
    stock_min = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='Stock FIn')
    m1 = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='m1')
    m2 = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='m2')
    m3 = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='m3')
    v1 = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='v1')
    v2 = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='v2')
    v3 = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='v3')
    vf1 = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='vf1')
    vf2 = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='vf2')
    vf3 = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='vf3')
    p1 = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='p1')
    p2 = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='p2')
    p3 = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='p3')
    factor = models.DecimalField(max_digits=15, decimal_places=3, verbose_name=("Factor"))
    densidad = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='Densidad')
    tipo = models.TextField(verbose_name=("Tipo"))

    def _str_(selfs):               #permite transformar los objetos a string
        return selfs.cod_activo
    
    class Meta:
        verbose_name = 'Inventario'
        verbose_name_plural = 'Inventarios'
        ordering: ['cod_inven']


class Clientes(models.Model):
    nombre = models.TextField(verbose_name="Nombre")
    cedula = models.TextField(verbose_name="Cedula", unique=True, null=True)
    telefono = models.TextField(verbose_name="Telefono")
    correo = models.TextField(verbose_name="correo")
    direccion = models.TextField(verbose_name="Direccion")
    tipo = models.TextField(verbose_name="Tipo")
    cod_user = models.ForeignKey("Usuario", verbose_name=("Codigo Ususario"), on_delete=models.PROTECT)

    def _str_(selfs):
        return selfs.nombre
    
    class Meta:
        verbose_name = 'Cliente'
        verbose_name_plural = 'Clientes'
        ordering: ['nombre']

class Doctores(models.Model):
    nombre = models.TextField( verbose_name="Nombre")
    telefono = models.TextField( verbose_name="Telefono")
    cedula = models.TextField( verbose_name="cedula", unique=True, null=True)
    direccion = models.TextField( verbose_name="direccion")
    ciudad = models.TextField( verbose_name="Ciudad")
    cod_user = models.ForeignKey("Usuario", verbose_name=("Codigo Ususario"), on_delete=models.PROTECT)

    def _str_(selfs):
        return selfs.nombre
        
    class Meta:
        verbose_name = 'Doctor'
        verbose_name_plural = 'Doctores'
        ordering: ['Nombre'] 

class Formulas(models.Model):
    cod_formula = models.TextField(verbose_name=("Codigo Formula"),unique=True)
    nombre = models.TextField(verbose_name=("Nombre Formula"))
    comp_activos = models.TextField(verbose_name=("Composicion"))
    cantidad = models.IntegerField(verbose_name=("Cantidad"))
    unidad_compra = models.TextField(verbose_name=("Unidad de compra"))
    #dosis hace referencia a cuantos dias va ha tomar el medicamento
    dosis = models.IntegerField(verbose_name=("Dosis")) 
    # la posologia hace referencia a cuantas pasillas diarias debe tomar
    posologia = models.IntegerField(verbose_name=("Posologia"))

    def _str_(selfs):
        return selfs.cod_formula
        
    class Meta:
        verbose_name = 'Formula'
        verbose_name_plural = 'Formulas'
        ordering: ['cod_formula']

class Pedidos(models. Model):
    descripcion = models.TextField(verbose_name=("Descripcion"))
    fecha = models.DateTimeField(verbose_name=("Fecha"), auto_now_add=True)
    estado = models.TextField(verbose_name=("Estado"))
    cantidad = models.IntegerField(verbose_name=("Cantidad"))
    observaciones = models.TextField(verbose_name=("Observaciones"))
    cod_user = models.ForeignKey("Usuario", verbose_name=("Codigo Usuario"), on_delete=models.PROTECT)
    cod_cliente = models.ForeignKey("Clientes", verbose_name=("Codigo Cliente"), on_delete=models.PROTECT)
    cod_doc = models.ForeignKey("Doctores", verbose_name=("Codigo Doctor"), on_delete=models.PROTECT)



    def _str_(selfs):
        return selfs.cod_pedido
        
    class Meta:
        verbose_name = 'Pedido'
        verbose_name_plural = 'Pedidos'
        ordering: ['cod_pedido']

class Visitas(models.Model):
    nombre = models.TextField(verbose_name=("Nombre"))
    cedula = models.TextField(verbose_name=("Cedula"),unique=True, null=True)
    telefono = models.TextField(verbose_name=("Telefono"))
    correo = models.TextField(verbose_name=("Correo"))
    direccion = models.TextField(verbose_name=("Direccion"))
    tipo = models.IntegerField(verbose_name=("Tipo"))
    cod_user = models.ForeignKey("Usuario", verbose_name=("Codigo Usuario"), on_delete=models.PROTECT)

    def _str_(selfs):
        return selfs.nombre
        
    class Meta:
        verbose_name = 'Visita'
        verbose_name_plural = 'Visitas'
        ordering: ['nombre']