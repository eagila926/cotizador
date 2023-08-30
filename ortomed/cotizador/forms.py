from django.forms import *
from .models import Inventario, Usuario

class InventarioForm(ModelForm):
    
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        
        self.fields['cod_inven'].widget.attrs['autofocus'] = True

        
    class Meta:
        model = Inventario
        fields = '__all__'
        widgets = {

            'cod_inven': TextInput(
                attrs={
                    'placeholder': 'Ingrese el codigo del producto',
                }
            ),
            'descripcion': TextInput(
                attrs={
                    'placeholder': 'Ingrese el nombre del producto',
                }
            ),
            'valor_costo': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'valor_venta': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'unidad_compra': TextInput(
                attrs={
                    'placeholder': 'Ingrese un la unidad',
                }
            ),
            'stock': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'stock_min': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'm1': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor ',
                }
            ),
            'm2': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'm3': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'v1': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'v2': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'v3': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'vf1': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'vf2': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'vf3': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'p1': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'p2': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor ',
                }
            ),
            'p3': TextInput(
                attrs={
                    'placeholder': 'Ingrese el valor',
                }
            ),
            'factor': TextInput(
                attrs={
                    'placeholder': 'Ingrese el factor de disolucion',
                }
            ),
            'densidad': TextInput(
                attrs={
                    'placeholder': 'Ingrese la densidad',
                }
            ),
            #Poner un selec para manejar una sola linea de los tipos del inventario#
            'tipo': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
        }

class UsuarioForm(ModelForm):
    
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # for form in self.visible_fields():
        #     form.field.widget.attrs['class'] = 'form-control'
        #     form.field.widget.attrs['autocomplete'] = 'off'
        self.fields['cod_inven'].widget.attrs['autofocus'] = True
        
    class Meta:
        model = Usuario
        fields = '__all__'
        widgets = {

            'cod_user': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'nombre': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'apellido': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'correo': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'tipo': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'ciudad': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            #Aqui poner un select para elegir entro los paises donde escollanos tiene operaciones#
            'pais': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            
        }

    