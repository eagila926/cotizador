
from django.contrib import admin
from django.urls import path

from cotizador.views import *
urlpatterns = [
    path('admin/', admin.site.urls),
    path('', login),
    

    path("inventario/list/", InventarioListView.as_view(), name="inventario_list"),
    path("inventario/add/", InventarioCreateview.as_view(), name="inventario_add"),
    path("formula/no_establecida/", FormulaCreateView.as_view(), name="formula_new"),
    #path('activosformulas/add/', ActivosFormulasAdd.as_view(), name='activosformulas_add'),


]

