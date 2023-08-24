from django.forms import *
from .models import Inventario, Usuario

class InventarioForm(ModelForm):
    
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # for form in self.visible_fields():
        #     form.field.widget.attrs['class'] = 'form-control'
        #     form.field.widget.attrs['autocomplete'] = 'off'
        self.fields['cod_inven'].widget.attrs['autofocus'] = True
        
    class Meta:
        model = Inventario
        fields = '__all__'
        widgets = {

            'cod_inven': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'descripcion': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'valor_costo': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'valor_venta': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'unidad_compra': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'stock': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'stock_min': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'm1': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'm2': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'm3': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'v1': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'v2': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'v3': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'vf1': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'vf2': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'vf3': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'p1': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'p2': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'p3': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'factor': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
                }
            ),
            'densidad': TextInput(
                attrs={
                    'placeholder': 'Ingrese un nombre',
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

    