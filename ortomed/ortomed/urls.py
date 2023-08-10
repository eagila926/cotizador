"""
URL configuration for ortomed project.

The `urlpatterns` list routes URLs to views. For more information please see:
    https://docs.djangoproject.com/en/4.2/topics/http/urls/
Examples:
Function views
    1. Add an import:  from my_app import views
    2. Add a URL to urlpatterns:  path('', views.home, name='home')
Class-based views
    1. Add an import:  from other_app.views import Home
    2. Add a URL to urlpatterns:  path('', Home.as_view(), name='home')
Including another URLconf
    1. Import the include() function: from django.urls import include, path
    2. Add a URL to urlpatterns:  path('blog/', include('blog.urls'))
"""
from django.contrib import admin
from django.urls import path

from cotizador.views import login, comercial, dashboard, etiquetas, facturas, formula, inventario, pedidos, reportes

urlpatterns = [
    path('admin/', admin.site.urls),
    path('', login),
    path('comercial/', comercial),
    path('dashboard/', dashboard),
    path('etiquetas/', etiquetas),
    path('facturas/', facturas),
    path('formula/', formula),
    path('inventario/', inventario),
    path('pedidos/', pedidos),
    path('reportes/', reportes)
]
