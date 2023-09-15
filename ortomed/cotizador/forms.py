from django.forms import *
from .models import *

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


class FormulaForm(ModelForm):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    class Meta:
        model = Formulas
        fields = '__all__'
        widgets = {
            'cod_formula': Select(attrs={
                'class': 'form-control select2',
                'style': 'with 100%'
            }),
            'nombre': TextInput(attrs={
                'class': 'form-control'
            }),
            'precio_venta': TextInput(attrs={
                'readonly': True,
                'class': 'form-control',
            }),
            'precio_compra': TextInput(attrs={
                'readonly': True,
                'class': 'form-control',
            }),
            'cod_user': Select(attrs={
                'class': 'form-control select2',

            }),
            'cod_cliente': TextInput(attrs={
                
                'class': 'form-control',
                
            }),
            'cod_doc': TextInput(attrs={
                
                'class': 'form-control',
            }),
             'cant': TextInput(attrs={
                'class': 'form-control',
                
            }),
             'dosis': TextInput(attrs={
                'class': 'form-control'
            }),
             'posologia': TextInput(attrs={
                'readonly': True,
                'class': 'form-control'
            }),     

        }

class ActivosFormulasForm(ModelForm):
    class Meta:
        model = ActivosFormulas
        fields = ['codigoOdoo', 'activo', 'cantidad', 'unidad']