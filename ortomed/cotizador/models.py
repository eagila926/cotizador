from datetime import datetime
from django.db import models
from django.forms import model_to_dict

from ortomed.settings import  STATIC_URL

# Create your models here.
class Usuario(models.Model):
    cod_user = models.TextField(verbose_name='Codigo', unique=True)
    nombre = models.TextField(verbose_name='Nombre')
    apellido = models.TextField(verbose_name='Apellido')
    correo = models.TextField(verbose_name='Correo')
    tipo = models.TextField(verbose_name='Tipo')
    ciudad = models.TextField(verbose_name='Ciudad')
    pais = models.TextField(verbose_name='Pais')

    def __str__(self):
        return self.cod_user
    
    class Meta:
        verbose_name = 'Usuario'
        verbose_name_plural = 'Usuarios'
        ordering: ['cod_user']
    

class Inventario(models.Model):
    cod_inven = models.IntegerField(verbose_name=("Codigo Inventario"), primary_key=True)
    descripcion = models.TextField(verbose_name='Descripcion')
    valor_costo = models.DecimalField(max_digits=20, decimal_places=4, verbose_name='Valor Costo')
    valor_venta = models.DecimalField(max_digits=20, decimal_places=4, verbose_name='Valor Venta')
    unidad_compra = models.TextField(max_length=3,verbose_name='Unidad Compra')
    stock = models.DecimalField(max_digits=15, decimal_places=4, verbose_name='Stock')
    stock_min = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='Stock FIn')
    factor = models.DecimalField(max_digits=15, decimal_places=3, verbose_name="Factor")
    densidad = models.DecimalField(max_digits=15, decimal_places=4,verbose_name='Densidad')
    tipo = models.TextField(verbose_name=("Tipo"))
    cant = models.IntegerField(verbose_name=("Cantidad"))

    def __str__(self):               #permite transformar los objetos a string
        return self.descripcion
    
    def toJSON(self):
        item = model_to_dict(self)
        item['cod_inven'] = "{:.2f}".format(self.cod_inven)
        item['descripcion'] = self.descripcion
        item['valor_costo'] = "{:.2f}".format(self.valor_costo)
        item['valor_venta'] = "{:.2f}".format(self.valor_venta)
        item['unidad_compra'] = self.unidad_compra
        item['stock'] = "{:.2f}".format(self.stock)
        item['stock_min'] = "{:.2f}".format(self.stock_min)
        item['factor'] = "{:.3f}".format(self.factor)
        item['densidad'] = "{:.4f}".format(self.densidad)
        item['tipo'] = self.tipo
        return item

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
    cod_user = models.ForeignKey("Usuario", verbose_name=("Codigo Ususario"), on_delete=models.CASCADE)

    def __str__(selfs):
        return selfs.nombre
    
    def toJSON(self):
        item = model_to_dict(self)
        item['cod_user'] = self.cod_user.toJSON()
        return item

    class Meta:
        verbose_name = 'Cliente'
        verbose_name_plural = 'Clientes'
        ordering: ['id']

class Doctores(models.Model):
    nombre = models.TextField( verbose_name="Nombre")
    telefono = models.TextField( verbose_name="Telefono")
    cedula = models.TextField( verbose_name="cedula", unique=True, null=True)
    direccion = models.TextField( verbose_name="direccion")
    ciudad = models.TextField( verbose_name="Ciudad")
    cod_user = models.ForeignKey("Usuario", verbose_name=("Codigo Ususario"), on_delete=models.CASCADE)

    def __str__(selfs):
        return selfs.nombre
    
    def toJSON(self):
        item = model_to_dict(self)
        item['cod_user'] = self.cod_user.toJSON()
        return item
        
    class Meta:
        verbose_name = 'Doctor'
        verbose_name_plural = 'Doctores'
        ordering: ['Nombre'] 

class Formulas(models.Model):
    cod_formula = models.TextField(verbose_name=("Codigo Formula"),unique=True)
    nombre = models.TextField(verbose_name=("Nombre Formula"))
    precio_venta = models.DecimalField(default=0.00, max_digits=15, decimal_places=2)
    precio_compra = models.DecimalField(default=0.00, max_digits=15, decimal_places=2)    
    cod_user = models.ForeignKey(Usuario, on_delete=models.CASCADE)
    cod_cliente = models.ForeignKey(Clientes, on_delete=models.CASCADE)
    cod_doc = models.ForeignKey(Doctores, on_delete=models.CASCADE)
    #dosis hace referencia a cuantos dias va ha tomar el medicamento
    dosis = models.IntegerField(default=0) 
    # la posologia hace referencia a cuantas pasillas diarias debe tomar
    posologia = models.IntegerField(default=0)
    cant = models.IntegerField(default=0)

    def __str__(self):
        return self.cod_user.nombre, self.cod_doc.nombre, self.cod_cliente.nombre
    
    def toJSON(self):
        item = model_to_dict(self)
        item['cod_user'] = self.cod_user.toJSON()
        item['cod_cliente'] = self.cod_cliente.toJSON()
        item['cod_doc'] = self.cod_doc.toJSON()
        item['precio_venta'] = format(self.precio_venta, '.2f')
        item['precio_compra'] = format(self.precio_compra, '.2f')
        item['det'] = [i.toJSON() for i in self.detformu_set.all()]
        return item

    class Meta:
        verbose_name = 'Formula'
        verbose_name_plural = 'Formulas'
        ordering: ['cod_formula']

class DetFormula(models.Model):
    formula = models.ForeignKey(Formulas, on_delete=models.CASCADE)
    activos = models.ForeignKey(Inventario, on_delete=models.CASCADE)
    cant = models.IntegerField(default=0)
    date_joined = models.DateField(default=datetime.now)
    obs = models.CharField(max_length=150, verbose_name='Observaciones')

    def toJSON(self):
        item = model_to_dict(self)
        item['formula'] = self.formula.toJSON()
        item['activos'] = self.activos.toJSON()
        item['cant'] = format(self.cant, '.2f')
        item['dosis'] = format(self.dosis, '.2f')
        item['posologia'] = format(self.posologia, '.2f')
        item['date_joined'] = self.date_joined.strftime('%Y-%m-%d')
        item['obs'] = format(self.obs, '.2f')
        return item
    
    class Meta:
        verbose_name = 'Detalle de Formula'
        verbose_name_plural = 'Detalle de Formulas'
        ordering = ['id']

class Visitas(models.Model):
    nombre = models.TextField(verbose_name=("Nombre"))
    cedula = models.TextField(verbose_name=("Cedula"),unique=True, null=True)
    telefono = models.TextField(verbose_name=("Telefono"))
    correo = models.TextField(verbose_name=("Correo"))
    direccion = models.TextField(verbose_name=("Direccion"))
    tipo = models.IntegerField(verbose_name=("Tipo"))
    cod_user = models.ForeignKey("Usuario", verbose_name=("Codigo Usuario"), on_delete=models.CASCADE)

    def __str__(selfs):
        return selfs.nombre
    
    def toJSON(self):
        item = model_to_dict(self)
        item['cod_user'] = self.cod_user.toJSON()
        return item
        
    class Meta:
        verbose_name = 'Visita'
        verbose_name_plural = 'Visitas'
        ordering: ['id']
