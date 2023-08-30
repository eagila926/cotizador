from typing import Any, Dict
from django.urls import reverse_lazy
from django.views.generic import ListView, CreateView, UpdateView
from django.http import HttpRequest, HttpResponse, JsonResponse
from django.shortcuts import render

from .models import Inventario
from .forms import InventarioForm


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