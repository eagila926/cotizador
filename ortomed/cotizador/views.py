import json
from typing import Any, Dict
from django.db import transaction
from django.urls import reverse_lazy
from django.utils.decorators import method_decorator
from django.views.decorators.csrf import csrf_exempt
from django.views.generic import ListView, CreateView, UpdateView
from django.http import HttpRequest, HttpResponse, JsonResponse
from django.shortcuts import render

from .models import *
from .forms import *


# Create your views here.
def login(request):
    return render( request, 'login.html')

class InventarioListView (ListView):
    model = Inventario
    template_name = 'inventario.html'

    def get_context_data(self, **kwargs):
        context =super().get_context_data(**kwargs)
        context['title'] = 'Inventario'
        
        return context
    
class InventarioCreateview(CreateView):
    model = Inventario
    template_name = 'form.html'
    form_class = InventarioForm
    success_url = reverse_lazy('inventario_list')
    url_redirect = success_url

    def get_context_data(self, request,*args,  **kwargs):
        return super().dispatch( request,*args,  **kwargs)
        
    def post(self, request, *args, **kwargs):
        data = {}
        try:
            action = request.POST[action]
            if action == 'add':
                form = self.get_form()
                data = form.save()
            else:
                data['error'] = 'No ha ingresado ninguna acción'
        except Exception as e:
            data['error'] = str(e)
        return JsonResponse(data)
    
    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context['title'] = 'Agregar Activo'
        context['entity'] = 'Inventario'
        context['list_url'] = self.success_url
        context['action'] = 'add'
        return context 

class InventarioUpdateview(UpdateView):
    def get_context_data(self, **kwargs):
        context =super().get_context_data(**kwargs)
        context['title'] = 'Agregar Inventario'
        
        return context

class FormulaCreateView (CreateView):
    model = Formulas
    form_class = FormulaForm
    template_name = 'create_formu.html'
    success_url = reverse_lazy('inventario_list')
    url_redirect = success_url

    @method_decorator(csrf_exempt)
    def dispatch(self, request, *args, **kwargs):
        return super().dispatch(request, *args, **kwargs)
    
    def post(self, request, *args, **kwargs):
        data={}
        try:
            action = request.POST['action']
            
            if action == 'search_activos':
                data = []
                activs = Inventario.objects.filter(descripcion__icontains=request.POST['term'])[0:10]
                for i in activs:
                    item = i.toJSON()
                    item['value'] = i.descripcion
                    data.append(item)
            elif action == 'add':
                with transaction.atomic():
                    formuls = json.loads(request.POST['formuls'])
                    formula = Formulas()
                    formula.cod_formula = formuls['cod_formula']
                    formula.nombre = formuls['nombre']
                    formula.precio_venta = float(formuls['precio_venta'])
                    formula.precio_compra = float(formuls['precio_compra'])
                    formula.cod_user = formuls['cod_user']
                    formula.save()
                    for i in formuls['activos']:
                        detformu = DetFormula()
                        detformu.formula = formula.id
                        detformu.activos = i['cod_inven']
                        detformu.cant = int(i['cant'])
                        detformu.dosis = int(i['dosis'])
                        detformu.posologia = int(i['posologia'])
                        detformu.date_joined = i['date_joined']
                        detformu.obs = i['obs']
                        detformu.save()
            else:
                data['error'] = 'No ha ingresado a ninguna opcion'
        except Exception as e:
            data['error'] = str(e)
        return JsonResponse(data, safe=False)
    
    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context['title'] = 'Creación de Formula'
        context['entity'] = 'Formulas'
        context['list_url'] = self.success_url
        context['action'] = 'add'
        context['detformu'] = []
        return context

class PedidoListView (ListView):
    model = Formulas
    template_name = 'pedidos.html'

    def get_context_data(self, **kwargs):
        context =super().get_context_data(**kwargs)
        context['title'] = 'Test'
        
        return context
  
    
    

