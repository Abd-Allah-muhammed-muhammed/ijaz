import {createContext, ReactElement, useContext, useState} from "react";
import {Conversation} from "@/types/models";

type ConversationContextType = {
  conversations: Conversation[],
  currentSocketId: string,
  searchValue: string,
  setConversations: (conversations: Conversation[]) => void,
  setCurrentSocketId: (socketId: string) => void,
  setSearchValue: (value: string) => void
  currentConversation: Conversation | null
  prevConversation: Conversation | null
  setCurrentConversation: (conversation: Conversation | null) => void
  setPrevConversation: (conversation: Conversation | null) => void
  updateConversationForNewMessages: (conversation: Conversation) => void
};


const ConversationContext = createContext<ConversationContextType | null>(null)


const ConversationProvider = ({children}: { children: ReactElement }) => {
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [currentConversation, setCurrentConversation] = useState<Conversation | null>(null);
  const [prevConversation, setPrevConversation] = useState<Conversation | null>(null);
  const [currentSocketId, setCurrentSocketId] = useState<string>('');
  const [searchValue, setSearchValue] = useState<string>('');
  const updateConversationForNewMessages = (conversation: Conversation) => {
    setConversations(prevConversations => {
      return [
        conversation,
        ...prevConversations.filter(c => c.id !== conversation.id)
      ];
    });
  }

  return (
    <ConversationContext.Provider value={
      {
        conversations,
        setConversations,
        currentConversation,
        setCurrentConversation,
        currentSocketId,
        setCurrentSocketId,
        searchValue,
        setSearchValue,
        prevConversation,
        setPrevConversation,
        updateConversationForNewMessages
      }
    }>
      {children}
    </ConversationContext.Provider>
  );
}

export const useConversations = () => {
  const context = useContext(ConversationContext);
  if (!context) {
    throw new Error("useConversations must be used within a ConversationProvider");
  }
  return context;
}

export {ConversationProvider, ConversationContext};
