import { Comment, Fragment, Text, type Slot, type VNode } from 'vue'

/**
 * ¿El slot pinta algo de verdad? `$slots.x` es truthy en cuanto el padre
 * declara el `<template #x>`, aunque dentro todo sea un v-if falso o un
 * v-for vacío; evaluando los vnodes evitamos pintar contenedores vacíos
 * (la parte inferior de las cards, con su padding y su divisoria).
 */
export function slotHasContent(slot?: Slot): boolean {
  return slot ? vnodesHaveContent(slot()) : false
}

function vnodesHaveContent(nodes: VNode[]): boolean {
  return nodes.some((node) => {
    if (node.type === Comment) return false
    if (node.type === Text) {
      return typeof node.children === 'string' && node.children.trim() !== ''
    }
    if (node.type === Fragment) {
      return Array.isArray(node.children) && vnodesHaveContent(node.children as VNode[])
    }
    return true
  })
}
